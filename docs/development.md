## Updating the vendor dir:

1. Run `composer update` in the root (plugin root).
2. Run `composer dump-autoload -o`
(for some reason, `vendor/composer/autoload_classmap.php` looses all its mappings on composer update). It also looses them on a `composer dump-autoload` (without the -o option).
It actually seems that the mappings are not needed. It seems to work fine when I alter autoload_real to not use the static loader. But well, I'm reluctant to change anything that works.

3. Remove unneeded files:

- Open bash
- cd into the folder

```
rm -r vendor/rosell-dk/webp-convert/docs
rm -r vendor/rosell-dk/webp-convert/src/Helpers/*.txt
rm vendor/rosell-dk/dom-util-for-webp/phpstan.neon
rm composer.lock
rmdir vendor/bin
```

3. Commit on git


## Copying WCFM
I created the following aliases in my `~/bash_aliases` file to make things easier...
(Usually I only need one of the last three - the first ones are subcommands)

```
alias lswcfmcss="ls /home/rosell/github/webp-express/lib/wcfm | grep 'index.*css' | tr '\n' ' ' | sed 's/\s//'"
alias lswcfmjs="ls /home/rosell/github/webp-express/lib/wcfm | grep 'index.*js' | tr '\n' ' ' | sed 's/\s//'"
alias updatewebpexpresscss="sed -i \"s/index\..*\.css/$(lswcfmcss)/g\" /home/rosell/github/webp-express/lib/classes/WCFMPage.php"
alias updatewebpexpressjs="sed -i \"s/index\..*\.js/$(lswcfmjs)/g\" /home/rosell/github/webp-express/lib/classes/WCFMPage.php"
alias remove_css_in_webp_express="rm -f /home/rosell/github/webp-express/lib/wcfm/index*.css"
alias remove_js_in_webp_express="rm -f /home/rosell/github/webp-express/lib/wcfm/index*.js"
alias remove_vendorjs_in_webp_express="rm -f /home/rosell/github/webp-express/lib/wcfm/vendor*.js"
alias remove_assets_in_webp_express="remove_css_in_webp_express && remove_js_in_webp_express"
alias copycss="cp ~/github/webp-convert-filemanager/dist/assets/index*.css /home/rosell/github/webp-express/lib/wcfm/"
alias copymainjs="cp ~/github/webp-convert-filemanager/dist/assets/index*.js /home/rosell/github/webp-express/lib/wcfm/"
alias copyvendorjs="cp ~/github/webp-convert-filemanager/dist/assets/vendor*.js /home/rosell/github/webp-express/lib/wcfm"
alias copyassets="remove_assets_in_webp_express && copymainjs && copyvendorjs && copycss"
alias copywcfm="copyassets && updatewebpexpresscss && updatewebpexpressjs"
alias buildwcfm="npm run build --prefix ~/github/webp-convert-filemanager"
alias freshwcfm="buildwcfm && copywcfm"
```
