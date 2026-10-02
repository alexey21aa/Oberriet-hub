# Swiss hosting options — checked 2026-10-01 UTC

No purchase is made or required to inspect, develop or test the package. A temporary trial is not permanent free production hosting. GitHub Pages serves static files and cannot run this PHP/WordPress application. WPVibe manages a connected WordPress instance; its connector alone does not supply PHP hosting, a database or a registered domain.

| Provider | Advertised starting price | Relevant offer | Official source |
|---|---:|---|---|
| hosttech Smart Deal | CHF 6.90/month + CHF 9.90 setup | 50 GB NVMe, 512 MB RAM, 10 MariaDB databases, Swiss servers, SSL; annual payment/12-month term, not permanent free hosting | <https://www.hosttech.ch/webhosting/angebote-vergleichen/> |
| Infomaniak Web Hosting | CHF 10.91/month | WordPress support, 250 GB SSD, 20 sites, automatic backup/restore; advertised 30-day trial | <https://www.infomaniak.com/fr/hebergement/hebergement-web> |
| Hostpoint Standard | CHF 15.90/month | 100 GB NVMe, 10 MariaDB databases, 30-day backups; advertised 30-day trial | <https://www.hostpoint.ch/webhosting/webhosting.html> |

Prices are the public advertised figures, not an accepted quote. Billing period, taxes, renewal terms, domain and selected extras must be checked in checkout before payment. Hostpoint advertises `.ch` domains at CHF 15/year, with a CHF 5 first-year promotion on the checked page: <https://www.hostpoint.ch/domains/domains.html>. Do not assume the promotion will remain available.

Infomaniak describes its own Swiss data centres: <https://www.infomaniak.com/en/about>. Hostpoint identifies Switzerland as the server location: <https://www.hostpoint.ch/webhosting/webhosting-angebote.html>. Confirm the contracted product, backups, support subprocessors and data-processing terms, rather than substituting a general marketing statement for the chosen deployment's actual facts.

For this WordPress MVP, standard shared PHP/MariaDB hosting is sufficient to start; an AI GPU server is not required. Prefer an existing authorised Swiss host where available. If none exists, retain the ready-to-install release and request the actual hosting authorisation/payment only when needed. Do not purchase a cloud server or paid model as a hidden dependency.

Before opening production, configure HTTPS, real cron, backups/restore, TOTP, operator/contact/address, host/country and private server logs. Exclude `.git`, source/tests, backups, secrets and the Git bundle from the public document root. Apply the bundled server examples through the host's supported configuration; they are examples and not evidence of an already secured live host.

Recommended lowest inspected starting price: hosttech Smart Deal. Advertised base year: CHF 82.80 + CHF 9.90 setup = CHF 92.70 before domain/extras. No order placed. Capacity must be checked with the final database workload, particularly batch imports at 512 MB RAM.
