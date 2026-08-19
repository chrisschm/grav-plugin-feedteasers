# v1.0.9
## unreleased

1. [](#improved)
    * Release tags are now bare semantic versions (`1.0.8`) instead of `v
	-prefixed, matching Grav's GPM convention for version sorting and
	`releases/latest`

# v1.0.8
## 08/09/2026 ([d9818ac](https://codeberg.org/chschmidt/grav-plugin-feedteasers/commit/d9818aca40da24aa2fd83ce7ec185b20e12d7724))

1. [](#new)
   * Estonian language file added

# v1.0.7
## 08/06/2026 (Release deleted)

1. [](#new)
    * SSRF protection for feed fetching (`classes/Http/SsrfGuard.php`): 
      private/local addresses are blocked, HTTP redirects are followed manually
      with each hop re-checked, and the IP is pinned via `CURLOPT_RESOLVE` to
      prevent DNS rebinding. New optional configuration setting
      `ssrf_allowed_hosts` (only in `feedteasers.yaml`, no admin interface field).

# v1.0.6
## 07/28/2026 ([25980ff](https://codeberg.org/chschmidt/grav-plugin-feedteasers/commit/25980ff056f295435abb31463617716da1a784e5))

1. [](#new)
    * i18l functionality added (de/en), plugin description stays german

# v1.0.5
## 07/21/2026 ([03e0fc7](https://codeberg.org/chschmidt/grav-plugin-feedteasers/commit/03e0fc7c3854e5139a6b30e8587020ad3be2044f))

1. [](#bugfix)
    * version number fixed

# v1.0.4
## 07/21/2026 ([1c47d9f](https://codeberg.org/chschmidt/grav-plugin-feedteasers/commit/1c47d9f09d88be7521edf66918dea31e29b46220))

1. [](#new)
    * added bugtracker and documentation links
      to admin panel

# v1.0.3
## 07/06/2026 ([6c1e2f2](https://codeberg.org/chschmidt/grav-plugin-feedteasers/commit/6c1e2f2ac26242a00d965f229b0e7e5ba8c40763))

1. [](#bugfix)
    * The increase of the version number was missing,
      so the update was never triggered

# v1.0.2
## 07/06/2026 ([e43deb0](https://codeberg.org/chschmidt/grav-plugin-feedteasers/commit/e43deb0e3acc346be908e8102cd33efb06b88bd8))

1. [](#bugfix)
    * fallback image was not preprocessed properly

# v1.0.1
## 07/06/2026 ([d1b512a](https://codeberg.org/chschmidt/grav-plugin-feedteasers/commit/d1b512ad9cc5fde1e06bd3f3cbdec5014c3d7007))

1. [](#new)
    * added fallback image for teasers without image

# v1.0.0
## 07/05/2026 ([201d03c](https://codeberg.org/chschmidt/grav-plugin-feedteasers/commit/201d03c42f88143048fc326cef0e39e5d4c8e8c6))

1. [](#new)
    * Initial Release
