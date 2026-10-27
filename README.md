# astrx/library

Building blocks of [AstrX](https://github.com/Vxmdzhb/astrx), on top of
`astrx/framework`:

- **Database** — the PDO connection factory and its diagnostics.
- **Auth** — the authorization gate, permissions and policies.
- **Api** — API key authentication.
- **Captcha** — image captchas rendered without JavaScript.
- **Image** — upload validation and re-encoding.
- **Identicon** — generated avatars.
- **Mail** — SMTP and IMAP clients and mailbox management.

`resources/` holds the captcha partial, the captcha catalog, these components'
diagnostic messages (en, it) and their default configuration.

AstrX isn't on Packagist and doesn't tag versions: you install from each
repository's `main`, and your `composer.lock` pins the exact commit. Tell
Composer where to find the package and the AstrX packages it depends on:

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        { "type": "vcs", "url": "https://github.com/Vxmdzhb/library" },
        { "type": "vcs", "url": "https://github.com/Vxmdzhb/framework" }
    ]
}
```

Then:

```sh
composer require astrx/library:dev-main
```

Issues and pull requests are welcome here. [AstrX](https://github.com/Vxmdzhb/astrx)
is the site that puts all the packages together.

## License

Released under the [Do Whatever License](LICENSE), with a code of conduct
in the same spirit: [CODE_OF_CONDUCT](CODE_OF_CONDUCT).
