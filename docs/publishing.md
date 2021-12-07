*These instructions are actually just notes for myself*. Some commands work only in my environment.

If it is only the README.txt that has been changed:

1. Validate the readme: https://wordpress.org/plugins/developers/readme-validator/
2. Update the tag below to current
3. Update the commit message below
4. Run the below
```
cd /var/www/we/svn/
cp ~/github/webp-express/README.txt trunk
cp ~/github/webp-express/README.txt tags/0.20.1
svn status
svn ci -m 'minor change in README (now tested with Wordpress 5.8 RC2)'
```

After that, check out if it is applied, on http://plugins.svn.wordpress.org/webp-express/

and here:
https://wordpress.org/plugins/webp-express/

'changelog.txt' changed too?
WELL - DON'T PUBLISH THAT, without publishing a new release. Wordfence will complain!

-------------------





before rsync, do this:

- Run `composer update` in plugin root (and remove unneeded files. Check development.md !)
  1. `composer update`
  2. `composer dump-autoload -o`
  3.  
    rm -r vendor/rosell-dk/webp-convert/docs
    rm -r vendor/rosell-dk/webp-convert/src/Helpers/*.txt
    rm vendor/rosell-dk/dom-util-for-webp/phpstan.neon
    rm composer.lock
    rmdir vendor/bin

- Make sure you remembered to update version in:
  1. the *webp-express.php* file
  2. in `lib/options/enqueue_scripts.php`
  3. in `lib/classes/ConverterHelperIndependent.php`
  4. in `README.txt` (Stable tag) - UNLESS IT IS A PRE-RELEASE :)
- Perhaps make some final improvements of the readme.
    Inspiration: https://www.smashingmagazine.com/2011/11/improve-wordpress-plugins-readme-txt/
https://pippinsplugins.com/how-to-properly-format-and-enhance-your-plugins-readme-txt-file-for-the-wordpress-org-repository/
- Make sure you upgraded the *Upgrade Notice* section.
- Skim: https://codex.wordpress.org/Writing_a_Plugin
- https://developer.wordpress.org/plugins/wordpress-org/
- Validate the readme: https://wordpress.org/plugins/developers/readme-validator/
- Make sure you have pushed the latest commits to github
- Make sure you have released the new version on github

And then:

```
cd /var/www/we/svn
svn up
```

If you have deleted folders (check with rsync --dry-run), then do this:
```
cd trunk
svn delete [folder]             (ie: svn delete lib/options/js/0.14.5). It is ok that the folder contains files
svn ci -m 'deleted folder'
```
(workflow cycle: http://svnbook.red-bean.com/en/1.7/svn.tour.cycle.html)

Then time to rsync into trunk:
dry-run first:
```
cd /var/www/we/svn
rsync -avh --dry-run --exclude '.git' --exclude '.github' --exclude='composer.lock' --exclude='scripts' --exclude='vendor/rosell-dk/webp-convert/.git' --exclude='vendor/rosell-dk/webp-convert/.git' --exclude='.gitignore' ~/github/webp-express/ /var/www/we/svn/trunk/  --delete
```

```
cd /var/www/we/svn
rsync -avh --exclude '.git' --exclude '.github'            --exclude='composer.lock' --exclude='scripts' --exclude='vendor/rosell-dk/webp-convert/.git' --exclude='.gitignore' ~/github/webp-express/ /var/www/we/svn/trunk/  --delete
```

**It should NOT contain a long list of files! (unless you have run phpreplace)**

*- and then WITHOUT "--dry-run" (remove "--dry-run" from above, and run)*


## TESTING

1. Create a zip
   - Select all the files in trunk (not the trunk dir itself)
   - Save it to /var/www/we/pre-releases/version-number/webp-express.zip
2. Upload the zip to the LiteSpeed test site and test
   - Login to https://betasite.com.br/rosell/wp-admin/plugins.php
   - Go to Plugins | Add new and click the "Upload Plugin button"
3. Upload the zip to other sites and test
   - https://lutzenmanagement.dk/wp-admin/plugin-install.php
   - http://mystress.dk/wp-admin/plugin-install.php
   ... etc


### Committing
Add new and remove deleted (no need to do anything with the modified):
```
cd svn
svn stat                      (to see what has changed)
svn add --force .             (this will add all new files - https://stackoverflow.com/questions/2120844/how-do-i-add-all-new-files-to-svn)
svn status | grep '^!'        (to see if any files have been deleted)
svn status | grep '^!' | awk '{print $2}' | xargs svn delete --force          (this will delete locally deleted files in the repository as well - see https://stackoverflow.com/questions/4608798/how-to-remove-all-deleted-files-from-repository/33139931)
```

Then add a new tag
```
cd svn
svn cp trunk tags/0.25.0       (this will copy trunk into a new tag)
```

And commit!
```
svn ci -m '0.25.0'
```


After that, check out if it is applied, on http://plugins.svn.wordpress.org/webp-express/
And then, of course, test the update
... And THEN. Grab a beer and celebrate!

And lastly, check if there are any new issues on https://coderisk.com


# New:

# svn co https://plugins.svn.wordpress.org/webp-express /var/www/webp-express-tests/svn


BTW: Link til referral (optimole): https://app.impact.com/



# On brand new system:

1. Install svn
`sudo apt-get install subversion`

2. create dir for plugin, and `cd` into it
3. Check out
`svn co https://plugins.svn.wordpress.org/webp-express my-local-dir` (if in dir, replace "my-lockal-dir" with ".")
