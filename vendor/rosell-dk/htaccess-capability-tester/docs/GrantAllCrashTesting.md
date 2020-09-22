# Grant All Crash Testing

This library used to have a class for crash-testing specific .htaccess rules commonly used in an attempt to grant access to specific files. Such directives are however "dangerous" to use because it is not uncommon that the server has been configured not to allow authorization directives like "Order" and "Require" and even set up to go fatal.

I removed the class, as I found it a bit too specialized.
Here is the `.htaccess` it was testing:

```
# This .htaccess is here in order to test if it results in a 500 Internal Server Error.
# .htaccess files can result in 500 Internal Server Error when they contain directives that has
# not been allowed for the directory it is in (that stuff is controlled with "AllowOverride" or
# "AllowOverrideList" in httpd.conf)
#
# The use case of a .htaccess file like the one tested here would be an attempt to override
# meassurements taken to prevent access. As an example, in Wordpress, there are security plugins
# which puts "Require all denied" into .htaccess files in certain directories in order to strengthen
# security. Such security meassurements could even be applied to the plugins directory, as plugins
# normally should not need PHPs to be requested directly. But of course, there are cases where plugin
# authors need to anyway and thus find themselves counterfighting the security plugin with an .htaccess
# like this. But in doing so, they run the risk of the 500 Internal Server Error. There are standard
# setups out there which not only does not allow "Require" directives, but are configured to go fatal
# about it.
#
# The following directives is used in this .htaccess file:
# - Require  (Override: AuthConfig)
# - Order (Override: Limit)
# - FilesMatch (Override: All)
# - IfModule  (Override: All)

# FilesMatch should usually be used in this use case, as you would not want to be granting more access
# than you need
<FilesMatch "ping\.txt$">
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Allow from all
    </IfModule>
    <IfModule mod_authz_core.c>
        Require all granted
    </IfModule>
</FilesMatch>
```
