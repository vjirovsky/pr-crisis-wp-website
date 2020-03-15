# PR-crisis WordPress microsite - resources

## Architecture
Idea is based on publishing static HTML exports outside VM - visitors don't interact with our WP, but with files in Azure CDN.

VM with LAMP installation is isolated and accessible via VPN (or at least protected by IP filter) for redactors only. WP is configured for another URL (e.g. mysite-administration.acme.company). Thanks to this setup, redactors are able to preview their work before publishing.

When is redactor satisfied with all articles, triggers WP2Static plugin to generate static HTML files to special folder located on VM file system.

On VM is also deployed systemd service - this service waits until hook from our custom Wordpress plugin, that HTML is finished (our middleware between WP2Static and systemd), and synchronize all these files with blob storage (origin of CDN) by Shared access secret (SAS) token. If something fails at this moment, end users are not affected, because Azure CDN cache have not been invalidated yet.

![Architecture for PR-crisis Wordpress website on Azure](architecture/crisis-wp-microsite-architecture.svg)

When are output files checked that everything is ok, redactor is able trigger invalidation of Azure CDN cache directly from WP user interface thanks to our custom WP plugin. Changes will take effect within 15 minutes for end users.

![](data:image/png;base64,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)



### Recommended Azure configuration

#### Azure CDN
1. set up Rules engine to redirect traffic from HTTP to HTTPS

    `If Request protocol Operator "Equals" Value "HTTP", Then URL Redirect Type "Found(302)" protocol "HTTPS"`

1. create Custom domain also for www. prefix
1. set up Rules engine to redirect traffic from www. prefix (in case of subdomain)

    `If Request URL Operator "Begins with" Request URL "http://www.myweb.company" Case transform "To lowercase", Then URL redirect Found (302) Protocol (HTTPS) Hostname (myweb.company)`
    
    and

    `If Request URL Operator "Begins with" Request URL "https://www.myweb.company" Case transform "To lowercase", Then URL redirect Found (302) Protocol (HTTPS) Hostname (myweb.company)`

#### Azure Blob Storage
1. do not forget to set up CORS as you need on website

## WP2Static configuration
- Detection level = As much as possible
- Destination URL = real URL of your website
- Target Directory = `/var/www/staticexport/$web`

## WordPress plugin for Azure CDN invalidation

Plugin adds button to purge Azure CDN Cache (via Azure REST API) from WP user interface. Also helps to server to detect, what new export is finished - creates file `uploads/deploy.txt`

#### Installation
- Upload plugin and activate it

- Fill in needed variables in `plugin.php`

## systemd script - detect new WP2Static export

Script detects when new export is finished and uploads on Blob storage.

#### Requirements
- `azcopy` tools - [how to install]([https://blog.elazem.com/2019/07/21/installing-azcopy-v10-on-linux)

#### Installation
- Copy files `deployer.path, deployer.service` from `deployer-script/` folder into `/etc/systemd/system`

- Copy file `deployer-script/deploy-to-azs.sh` folder into `/root/` 

- Fill in needed variables in `deploy-to-azs.sh`

- Run:

  <code>
    chmod +x /root/deploy-to-azs.sh

    systemctl daemon-reload

    systemctl start deployer

    systemctl enable deployer
  </code>

---

## Architecture - HA version
If you want to protect the solution against outage of Azure region, you can place in front of solution also service <i>Azure Traffic Manager</i>, and for VM replication <i>Azure Site Recovery</i> and double whole solution in other region(s).

![Architecture for PR-crisis Wordpress website in HA mode on Azure](architecture/crisis-wp-microsite-architecture-ha.svg)

## Credits
- Vaclav Jirovsky (https://www.vjirovsky.cz)
- Vladimir Smitka (https://www.lynt.cz)
