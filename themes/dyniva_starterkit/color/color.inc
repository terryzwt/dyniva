<?php

/**
 * @file
 * Lists available colors and color schemes for the CCMS Front theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'site-base'               => t('Site Base'),
    'site-second'             => t('Site Second'),
    'site-third'              => t('Site Third'),
    'bg-base'                 => t('Background base'),
    'bg-light'                => t('Background light'),
    'bg-lighter'              => t('Background lighter'),
    'text-color'              => t('Text color'),
    'header-color'            => t('Header color'),
    'disable-color'           => t('Disable color'),
    'link-color'              => t('Link color'),
    'border-color'            => t('Border color'),
    'btn-color'               => t('Button text color'),
    'btn-bg'                  => t('Button background color'),
    'btn-border-color'        => t('Button border color'),
    'icon-mark'               => t('Icon mark'),
    'loading-color'           => t('Loading color'),
    'table-bg-hover'          => t('Table tr hover color'),
    'table-border-color'      => t('Table border color'),
    'table-cell-even-bg'      => t('Table cell even background color'),
    'table-active-bg'         => t('Table active background'),
    'table-active-color'      => t('Table avtive color'),
    'element-bg-color'        => t('Form element background color'),
    'element-border'          => t('Form element border color'),
    'element-border-hover'    => t('Form element hover border color'),
    'element-border-focus'    => t('Form element focus border color'),
    'chooses-color'           => t('Form checkbox and radio color'),
    'switch-color'            => t('Form switch plugin color'),
    'progress-color'          => t('Progress color'),
    'progress-border-color'   => t('Progress border color'),
    'progress-track-bg'       => t('Progress track background color'),
    'progress-bar-bg'         => t('Progress bar background color'),
    'label-color'             => t('Labels color'),
    'label-border-color'      => t('Labels border color'),
    'label-bg-color'          => t('Labels background color'),
    'label-icon-color'        => t('Labels icon color')
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Davyin UI'),
      'colors' => [
        'site-base'               => '#1b72b1',
        'site-second'             => '#3ec6ff',
        'site-third'              => '#3ec6ff',
        'bg-base'                 => '#ffffff',
        'bg-light'                => '#e2e2e2',
        'bg-lighter'              => '#f5f5f5',
        'text-color'              => '#616161',
        'header-color'            => '#212121',
        'disable-color'           => '#9e9e9e',
        'link-color'              => '#1b72b1',
        'border-color'            => '#c6c6c6',
        'btn-color'               => '#ffffff',
        'btn-bg'                  => '#1b72b1',
        'btn-border-color'        => '#1b72b1',
        'icon-mark'               => '#222222',
        'loading-color'           => '#eeeeee',
        'table-bg-hover'          => '#f5f5f5',
        'table-border-color'      => '#dddddd',
        'table-cell-even-bg'      => '#FBFBFC',
        'table-active-bg'         => '#222222',
        'table-active-color'      => '#1b72b1',
        'element-bg-color'        => '#ffffff',
        'element-border'          => '#c6c6c6',
        'element-border-hover'    => '#bbbbbb',
        'element-border-focus'    => '#9A9A9A',
        'chooses-color'           => '#1b72b1',
        'switch-color'            => '#1b72b1',
        'progress-color'          => '#ffffff',
        'progress-border-color'   => '#c6c6c6',
        'progress-track-bg'       => '#f6f6f6',
        'progress-bar-bg'         => '#1b72b1',
        'label-color'             => '#ffffff',
        'label-border-color'      => '#3ec6ff',
        'label-bg-color'          => '#3ec6ff',
        'label-icon-color'        => '#ffffff'
      ],
    ]
  ],
  // CSS files (excluding @import) to rewrite with new color scheme.
  // If key is less file ,replace the css with complied less.
  'css' => [
    'assets/less/index.less' => 'build/init.css',
  ],

  // Files to copy.
  'copy' => [
    'logo.svg',
  ],

  // Preview files.
  'preview_library' => 'dyniva_ui/color.preview',
  'preview_html' => 'color/preview.html',

  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'dyniva_ui'),
      ],
    ],
  ],
];
