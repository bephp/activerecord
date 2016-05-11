#!/bin/bash 

./vendor/bin/phpdoc -t doc -f ActiveRecord.php
cd doc
git init
git config user.name "Travis CI"
git config user.email "lloydzhou@qq.com"
git add .
git commit -m "Deploy to GitHub Pages"
git push --force --quiet "https://$GH_TOKEN@$GH_REF" master:gh-pages > /dev/null 2>&1

