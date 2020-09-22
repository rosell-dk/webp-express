"test.php" will either result in "0", "1" or an error.

The tester class then makes a HTTP to `test.php` and examines the response in order to answer the question: *Is "RequestHeader" available and does it work?* It should be clear by inspecting the code above that if `mod_headers` is loaded and `RequestHeader` is allowed in the `.htaccess`, the response of `test.php` will be "1". And if `mod_headers` isn't loaded, the response will be "0". There is however other possibilities. The server can be configured to completely ignore `.htaccess` files (when `AllowOverride` is set to *None* and `AllowOverrideList` is set to *None*). In that case, we will also get a "0", which is appropriate, as this would also mean a "no" to the "available and working?" question. Also, the `RequestHeader` directive might have been disallowed. Exactly which directives that are allowed in an `.htaccess` depends on the configuration of the virtual host and can be set up differently per directory. What happens then, if the directive is forbidden? One of two things. Depending on the "NonFatal" option on the "AllowOverride" directive, Apache will either go fatal on forbidden directives or ignore them. In this case, the ignored directive will result in a "0", which is appropriate. "Going fatal" means responding with a *500 Internal Server Error*. So the tester class must interpret such response as a "no" to the "available and working?" question. Other errors are possible. For example *404 Not Found*. In that case, the problem is probably that the test was set up wrong and throwing an Exception is appropriate. How about *429 Too Many Requests*? It would mean that the test could not be run *at this time* and an inconclusive answer would seem appropriate. However, you could also argue that as the test failed its purpose (being conclusive), an Exception is appropriate. Throwing exceptions allows users to handle the various cases differently, which is nice. So we go with Exceptions. *403 Forbidden*? I'm unsure. Often, the directory will be forbidden for all users, and then a "no" seems appropriate, however, it could be that it is just forbidden for some users, and in that case, an inconclusive answer seems more suitable - which means throwing an Exception. TODO: decide on this. Summing up: "1" => true, "0" => false, Error => false or throw Exception, depending on the error.



Here is another example, on how

For example, the following two files can be used for answering the question: Is mod_env loaded?

**.htaccess**
```
<IfModule mod_setenvif.c>
ServerSignature On
</IfModule>
<IfModule !mod_setenvif.c>
ServerSignature Off
</IfModule>
```

**test.php**
```
if (isset($_SERVER['SERVER_SIGNATURE']) && ($_SERVER['SERVER_SIGNATURE'] != '')) {
    echo 1;
} else {
    echo 0;
}
```
I'm a bit proud of this one. The two directives used (`ServerSignature` and `IfModule`) are both part of core. So these will be available, unless Apache is configured to ignore `.htaccess` files altogether in the given directory. The rarely used `ServerSignature` directive has an even more rarely known side-effect: It sets a server variable. By making a request to `test.php`, we

 directive is part of core, as is  And as you see, the template can easily be modified to test for whatever module.  can easily be used to test whatever  `ServerSignature` is the only directive that is part of core
