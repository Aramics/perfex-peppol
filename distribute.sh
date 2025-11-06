#!/bin/bash
#remove DSTORE
basedir="/Users/Aramics/Projects/web/TurnSaas/delivery-notes-perfex"
sudo find $basedir -name ".DS_Store" -depth -exec rm {} \;
isKnown=True

#make translations
cd /Users/Aramics/Projects/web/TurnSaas/tools;
./translate_lang_files.sh "$basedir/delivery_notes/language";
cd -;

if [[ "$isKnown" == True ]]; then
    name="delivery_notes"
    tmpBasedir="$basedir/tmp";
    tmpFolder="tmp/$name";
    filename="$name.zip"

    mkdir "$basedir/tmp" || echo "tmp folder Already exist";
    rm -r "$basedir/$tmpFolder" || echo "Tmp folder copy dont exist";
    
    #copy file to tmp
    cp -r "$basedir/$name" "$basedir/$tmpFolder";

    #remove the custom lang files and others from tmp
    find $basedir/$tmpFolder/ -name "custom_lang.php" -depth -exec rm {} \;

    #zip the tmp folder content
    cd $tmpBasedir && zip -r $basedir/$filename "$name" && cd -;

    # build documentation
    cd $basedir/mkdocs-documentation && python3 -m mkdocs build && cd -;
    rm -r $basedir/MainFiles/documentation;
    mv $basedir/mkdocs-documentation/site $basedir/MainFiles/documentation;

    #copy file to main files folder
    mv -f $basedir/$filename $basedir/MainFiles/;

    #zip mainfiles folder
    cd $basedir && zip -r "$basedir/MainFiles.zip" "MainFiles" && cd -;

    #remove tmp folder
    rm -r "$basedir/$tmpFolder";
fi