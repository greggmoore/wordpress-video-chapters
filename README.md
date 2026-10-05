# WordPress Video Chapters

An original WordPress plugin example: editors enter chapter timestamps, and viewers use a responsive chapter list to navigate a native HTML5 video. Created with Codex assistance as a new code sample; this is not presented as previously shipped client work or as authorship of the VidoRev theme.

## Reviewer guide

- `blumoo-video-chapters.php`: WordPress hooks, editor UI, all-or-nothing validation, metadata persistence, shortcode and escaped output.
- `assets/chapters.js`: component-scoped interaction, active chapter tracking and media error handling.
- `assets/chapter-state.js`: independently testable navigation boundaries.
- `assets/chapters.css`: responsive layout and visible keyboard focus.
- `tests/chapter-state.test.js`: Node tests for chapter boundaries and unavailable media.

## Features

- Chapter editor for posts and pages; accepts `MM:SS | Label` or `HH:MM:SS | Label`.
- Sorts timestamps and rejects duplicates, malformed rows, more than 100 chapters, and labels longer than 240 bytes.
- Rejects an invalid batch without replacing existing metadata; retains the attempted input for one hour, scoped to the editor and post.
- Nonce and capability checks; skips autosaves and revisions.
- Native video controls, keyboard-operable chapter buttons and current-chapter indication. Seeking does not force playback.
- Multiple instances have independent state. Buttons remain disabled until finite media metadata is available; chapters outside the duration remain disabled.

## Installation and example

1. Copy this folder to `wp-content/plugins/wordpress-video-chapters` and activate **Blumoo Video Chapters**.
2. Edit a post or page. In **Video chapters**, enter a direct HTTP(S) `.mp4`, `.webm` or `.ogv` URL and chapter rows:

   ```text
   00:00 | Introduction
   00:45 | The challenge
   02:10 | Design decisions
   04:30 | Results
   ```

3. Add a Shortcode block containing `[blumoo_video_chapters]` and save.
4. Open the post, load the video and select a chapter. Playback remains paused if it was paused.

Target: WordPress 6.4+ and PHP 7.4+. No build step or JavaScript framework required.

## Optional VidoRev integration

If the plugin URL is empty, it reads the current post's `vm_video_url` field. That field was identified in the supplied VidoRev package. Only direct supported media URLs are accepted. The shortcode renders its **own native player**; it does not control VidoRev's existing player, so avoid rendering duplicate players on the same page.

VidoRev is separately purchased software. No theme files, vendor code, fonts or bundled assets are redistributed here. This plugin also works without VidoRev when an explicit video URL is supplied.

## Validation

```sh
node --check assets/chapter-state.js
node --check assets/chapters.js
node --test tests/chapter-state.test.js
php -l blumoo-video-chapters.php
```

JavaScript syntax and Node boundary tests were checked during creation. PHP lint and WordPress runtime testing were not performed because PHP/WordPress were unavailable in the creation environment.

Manual checks before deployment: valid/invalid save batches and nonce failures; keyboard-only navigation; password-protected/private posts; two shortcodes on one page; failed media loads; timestamp beyond video duration; narrow screens; caption availability and screen-reader announcements.

## Scope and limitations

This is a focused source-review example, not a production certification. It does not implement YouTube/Vimeo adapters, live streaming, captions/transcripts, membership access rules, or media authorization. Use public media; the shortcode exposes its source URL to the viewer. WordPress password and post visibility checks do not replace a site's separate paywall policy. Supply captions and transcripts through a further player integration before using this for accessible editorial video. Browser codec support and seeking depend on the media and host.

The front-end status strings are English; PHP editor strings use the plugin text domain. Metadata is retained on deactivation. No external network requests are made by PHP.

## WordPress references

- [Meta boxes](https://developer.wordpress.org/reference/functions/add_meta_box/)
- [Saving posts](https://developer.wordpress.org/reference/hooks/save_post/)
- [Capability checks](https://developer.wordpress.org/reference/functions/current_user_can/)

## License

Original plugin code: GPL-2.0-or-later. This does not grant rights to the separately licensed VidoRev theme.
