import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Composer Translation Validator',
  description: 'A Composer plugin that validates translation files in your project',
  base: '/composer-translation-validator/',

  head: [
    ['link', { rel: 'icon', href: '/composer-translation-validator/logo.svg' }]
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/getting-started/' },
      { text: 'Configuration', link: '/configuration/' },
      { text: 'Reference', link: '/reference/cli' },
      {
        text: 'Links',
        items: [
          { text: 'Packagist', link: 'https://packagist.org/packages/move-elevator/composer-translation-validator' },
          { text: 'Changelog', link: 'https://github.com/move-elevator/composer-translation-validator/releases' }
        ]
      }
    ],

    sidebar: {
      '/getting-started/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Introduction', link: '/getting-started/' },
            { text: 'Installation', link: '/getting-started/installation' },
            { text: 'Quick Start', link: '/getting-started/quickstart' }
          ]
        }
      ],
      '/configuration/': [
        {
          text: 'Configuration',
          items: [
            { text: 'Overview', link: '/configuration/' },
            { text: 'Configuration File', link: '/configuration/config-file' },
            { text: 'Schema Reference', link: '/configuration/schema' }
          ]
        }
      ],
      '/reference/': [
        {
          text: 'Reference',
          items: [
            { text: 'CLI Reference', link: '/reference/cli' },
            { text: 'Validators', link: '/reference/validators' },
            { text: 'File Formats', link: '/reference/file-formats' },
            { text: 'File Detection', link: '/reference/file-detection' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/move-elevator/composer-translation-validator' }
    ],

    editLink: {
      pattern: 'https://github.com/move-elevator/composer-translation-validator/edit/main/docs/:path',
      text: 'Edit this page on GitHub'
    },

    footer: {
      message: 'Released under the GPL-3.0 License.',
      copyright: 'Copyright 2025-2026 move elevator GmbH'
    },

    search: {
      provider: 'local'
    }
  }
})
