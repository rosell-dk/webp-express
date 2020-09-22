# The many ways of .htaccess failure

If you have written any `.htaccess` files, you are probably comfortable with the "IfModule" tag and the concept that some directives are not available, unless a certain module has been loaded. So, to make your .htaccess failproof, you wrapped those directives in an IfModule tag. Failproof? Wrong!

Meet the [AllowOverride](https://httpd.apache.org/docs/2.4/mod/core.html#allowoverride) and [AllowOverrideList](https://httpd.apache.org/docs/2.4/mod/core.html#allowoverridelist) directives. These fellows effectively controls which directives that are allowed in .htaccess files. It is not a global setting, but something that can be configured per directory. If you are on a specialized host for some CMS, it could very well be that the allowed directives is limited and set to different things in the directories, ie the plugin and media directories.

The settings of AllowOverride and AllowOverrideList can produce three kinds of failures:

1. The .htaccess is skipped altogether. This happens when nothing is allowed (when both AllowOverride and AllowOverrideList are set to None)

2. The forbidden tags are ignored. This happens if the "Nonfatal" setting for AllowOverride is set to "All" or "Override"

3. All requests to the folder/subfolder containing an .htaccess file with forbidden directive results in a 500 Internal Server Error. This happens if the "Nonfatal" option isn't set (or is set to "Unknown"). The IfModule directive does not prevent this from happening.

So, no, using IfModule tags does not make the .htaccess failproof.

Fortunately, the core directives can only be made forbidden in what I take to be a very rare setting: By setting AllowOverride to None and AllowOverrideList to a list, which doesn't include the core directives. So at least, it will be rare to that the IfModule directive itself is forbidden and thereby can cause 500 Internal Server Error.

Besides these cases, there is of course also the authorization directives.
The sysadmin might have placed something like this in the virtual host configuration:

```
<Directory /var/www/your-site/media/ >
    <FilesMatch "\.php$">
        Require all denied
    </FilesMatch>
</Directory>
```

This isn't really a .htaccess failure, but it is an obstacle too. Especially with regards to this library. As we have seen, the capabilities of a .htaccess in one folder is not neccessarily the same in another folder, so we often want to place the .htaccess test files in a subdir to the directory that the real .htaccess files are going to reside. However, if phps aren't allowed to be run there, we can't. Unless of course, the test can be made not to rely on a receiving test.php script. A great amount of effort has been done to avoid resorting to PHP when possible.
