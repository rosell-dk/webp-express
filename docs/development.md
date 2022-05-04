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
rm composer.lock
rmdir vendor/bin
```

3. Commit on git


## Copying WCFM
I created the following script for building WCFM, copying it to webp-express, etc
```
#!/bin/bash

WCFM_PATH=/home/rosell/github/webp-convert-filemanager
WE_PATH=/home/rosell/github/webp-express
WE_PATH_WCFM=$WE_PATH/lib/wcfm
WCFMPage_PATH=/home/rosell/github/webp-express/lib/classes/WCFMPage.php
WC_PATH=/home/rosell/github/webp-convert

copyassets() {
  # remove assets in WebP Express
  rm -f $WE_PATH_WCFM/index*.css
  rm -f $WE_PATH_WCFM/index*.js
  rm -f $WE_PATH_WCFM/vendor*.js

  # copy assets from WCFM
  cp $WCFM_PATH/dist/assets/index*.css $WE_PATH_WCFM/
  cp $WCFM_PATH/dist/assets/index*.js $WE_PATH_WCFM/
  cp $WCFM_PATH/dist/assets/vendor*.js $WE_PATH_WCFM/


  #CSS_FILE = $(ls /home/rosell/github/webp-express/lib/wcfm | grep 'index.*css' | tr '\n' ' ' | sed 's/\s//')
  CSS_FILE=$(ls $WE_PATH_WCFM | grep 'index.*css' | tr '\n' ' ' | sed 's/\s//')
  JS_FILE=$(ls $WE_PATH_WCFM | grep 'index.*js' | tr '\n' ' ' | sed 's/\s//')


  if [ ! $CSS_FILE ]; then
    echo "No CSS file! - aborting"
    exit
  fi
  if [ ! $JS_FILE ]; then
    echo "No JS file! - aborting"
    exit
  fi

  echo "CSS file: $CSS_FILE"
  echo "JS file: $JS_FILE"

  # Update WCFMPage.PHP references
  sed -i "s/index\..*\.css/$CSS_FILE/g" $WCFMPage_PATH
  sed -i "s/index\..*\.js/$JS_FILE/g" $WCFMPage_PATH
}

if [ ! $1 ]; then
  echo "Missing argument. Must be buildfm, copyfm, build-copyfm or rsync-wc"
  exit
fi

buildwcfm() {
  npm run build --prefix $WCFM_PATH
}

if [ $1 = "copyfm" ]; then
  echo "copyfm"
  copyassets
fi

if [ $1 = "buildfm" ]; then
  echo "buildfm"
  buildwcfm
fi

if [ $1 = "build-copyfm" ]; then
  echo "build-copyfm"
  buildwcfm
  copyassets
fi

rsyncwc() {
  rsync -avh --size-only --exclude '.git' --exclude '.github' --exclude='composer.lock' --exclude='scripts' --exclude='vendor/rosell-dk/webp-convert/.git' --exclude='vendor/rosell-dk/webp-convert/.git' --exclude='.gitignore' "$WC_PATH/src/" "$WE_PATH/vendor/rosell-dk/webp-convert/src"  --delete
}

if [ $1 = "rsync-wc" ]; then
  echo "rsync-wc"
  rsyncwc
fi
```

# Instruction for installing development version, for non-developers :)

To install the development version:
1) Go to https://wordpress.org/plugins/webp-express/advanced/
2) Find the place where it says “Please select a specific version to download”
3) Click “Download”
4) Browse to /wp-admin/plugin-install.php (ie by going to the the Plugins page and clicking “Add new” button in the top)
5) Click “Upload plugin” (button found in the top)
6) The rest is easy
