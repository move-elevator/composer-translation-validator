# Performance Baseline

Tracks wall-clock and memory numbers for `ValidationOrchestrationService::executeValidation()`
so the impact of the open `perf/*` PRs can be measured before/after they land.

## Methodology

- **Fixture set**: synthetic, generated (not part of the test suite) — 25 domains × 2 languages
  (`en`/`de`) YAML files, 500 keys each (10 sections × 10 groups × 5 keys), 3 levels deep.
  50 files, ~24,925 keys total. A small percentage of entries carry placeholders, HTML tags,
  missing translations, or empty values so every validator has real work to do (not a zero-issue
  run).
- **What's measured**: `ValidationOrchestrationService::executeValidation()` called directly
  (bypassing the Composer command layer) with all validators from
  `ValidatorRegistry::getAvailableValidators()` and default `TranslationValidatorConfig`,
  against the fixture directory, non-recursive, auto-detected file grouping.
- **Runs**: 5 back-to-back invocations in the same PHP process; `gc_collect_cycles()` before each
  run. Reported: min / median / mean / max wall time (ms) per run, and peak memory
  (`memory_get_peak_usage(true)`) as a **process-level** high-water mark across all 5 runs —
  `gc_collect_cycles()` does not reset it, so it is not an independent per-run figure.
- **Environment**: PHP 8.5.0 (CLI, NTS), Apple M4 Pro, 14 cores, 24 GB RAM, macOS.
- Scripts used are not part of the repository (kept in a scratch location); the fixture generator
  and benchmark runner can be recreated on request if this needs to become a repeatable CI check.

## Baseline: `main` @ `c570fe0` (2026-07-28)

| Metric | Value |
|---|---|
| Files checked | 50 |
| Keys checked | 24,925 |
| Min | 742.34 ms |
| Median | 758.25 ms |
| Mean | 784.89 ms |
| Max | 897.95 ms |
| Peak memory | 32.00 MB |

Raw durations (ms): `742.34, 748.93, 758.25, 776.97, 897.95`

## Performance PRs reflected below

All merged into `main` via squash, one after another, 2026-07-28:

- #137 fix: parse PHP translation files via AST instead of executing them
- #138 fix: exclude PHP config files from auto-detection
- #139 fix: reject invalid validator classes in --only/--skip options
- #140 fix: do not follow symlinks during recursive file scan
- #141 fix: harden system path safety check with boundary matching
- #142 fix: strip control characters and escape markup in CLI output
- #143 fix: skip translation files exceeding a size limit
- #144 fix: reject DOCTYPE and disable network in XLIFF parser
- #145 perf: resolve XLIFF content via a memoized id map
- #146 perf: cache prepared XLIFF schema source per version
- #147 perf: memoize extractKeys() results in parsers
- #148 perf: cache validator severity and drop per-comparison reflection
- #149 perf: evict parser cache per file set to bound memory
- #150 perf: scan each directory once and bucket files by parser
- #151 perf: reuse parser-read file content in encoding validation
- #152 perf: return bool from validator validate() instead of building issue arrays
- #153 perf: memoize segment convention detection
- #154 perf: memoize placeholder extraction per value
- #155 perf: dedupe render keys via hash set instead of in_array
- #156 perf: minor render and file-collection optimizations
- #157 feat: add standalone PHAR distribution
- #158 feat: disable KeyNamingConventionValidator by default

## After: `main` @ `f8910bc` (2026-07-28)

Same fixture set, same methodology (5 runs), run immediately after the merge sequence above.

| Metric | Before (`c570fe0`) | After (`f8910bc`) | Δ |
|---|---|---|---|
| Files checked | 50 | 50 | — |
| Keys checked | 24,925 | 24,925 | — |
| Min | 742.34 ms | 565.73 ms | **-23.8%** |
| Median | 758.25 ms | 566.42 ms | **-25.3%** |
| Mean | 784.89 ms | 573.74 ms | **-26.9%** |
| Max | 897.95 ms | 601.49 ms | **-33.0%** |
| Peak memory | 32.00 MB | 28.00 MB | **-12.5%** |

Raw durations (ms): `565.73, 565.96, 566.42, 569.10, 601.49`

The combined effect of the 22 merged PRs (8 security/correctness fixes, 12 performance
optimizations, 2 features) is a validation run that is roughly a **quarter faster** and uses
**~12.5% less peak memory** on the same workload, with no change in what was validated (same file
and key counts). Note that #158 (disabling `KeyNamingConventionValidator` by default) is a
behavioral default change, not a code path optimization, and is not exercised by this benchmark
since it calls `ValidatorRegistry::getAvailableValidators()` directly rather than the
config-resolved default set — the perf gain above is attributable to the `perf/*` and `fix/*`
changes to the parsing/collection/rendering hot paths.
