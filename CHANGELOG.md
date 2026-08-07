# v1.0.8
## unreleased

1. [](#new)
   * Estonian language file added

# v1.0.7
## 08/06/2026

1. [](#new)
    * SSRF protection for feed fetching (`classes/Http/SsrfGuard.php`): 
      private/local addresses are blocked, HTTP redirects are followed manually
      with each hop re-checked, and the IP is pinned via `CURLOPT_RESOLVE` to
      prevent DNS rebinding. New optional configuration setting
      `ssrf_allowed_hosts` (only in `feedteasers.yaml`, no admin interface field).

# v1.0.6
## 07/28/2026

1. [](#new)
    * i18l functionality added (de/en), plugin description stays german

# v1.0.5
## 07/21/2026

1. [](#bugfix)
    * version number fixed

# v1.0.4
## 07/21/2026

1. [](#new)
    * added bugtracker and documentation links
      to admin panel

# v1.0.3
## 07/06/2026

1. [](#bugfix)
    * The increase of the version number was missing,
      so the update was never triggered

# v1.0.2
## 07/06/2026

1. [](#bugfix)
    * fallback image was not preprocessed properly

# v1.0.1
## 07/06/2026

1. [](#new)
    * added fallback image for teasers without image

# v1.0.0
## 07/05/2026

1. [](#new)
    * Initial Release
