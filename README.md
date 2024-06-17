This PHP webpage allows you to easily post GenBank accession numbers to iNaturalist observations.

It takes an AccessionReport.tsv file as input and automatically updates all the corresponding iNaturalist observations with the "Genbank Accession Number" observation field.

This program requires an iNaturalist application API key. If you don't already have one, you can register your application at https://www.inaturalist.org/oauth/applications.

To use this program, first install the files on a webserver that supports PHP. Then add your iNaturalist API key and account credentials to the `conf.sample.php` file and rename it `conf.php`. Next make sure that all of your iNaturalist observations have the "Accession Number" observation field set and that this number matches the Sequence ID set in your GenBank submission. Once your GenBank submission has been processed, GenBank will provide you with an AccessionReport.tsv file. This file should look something like:
```
#Accession	Sequence ID	Release Date
PP791215	HAY-F-006570	05/19/2024	
PP791216	HAY-F-006067	05/19/2024	
PP791217	HAY-F-006002	05/19/2024	
```

Once the steps above are complete, load the `inatgenbank.php` webpage in your web browser. Click "Choose File" and choose the AccessionReport.tsv file. Click "Submit" and wait for the script to finish processing. This may take several minutes depending on how many records are in the file. A maximum of 100 records can be processed at once.

If you would like the script to generate a log file, make sure that either the directory the script is in is writable or that there is a writable file in the diretory named `log.txt`.

Note that if the Sequence ID in the AccessionReport.tsv is an iNaturalist observation ID (i.e. a 9 or 10 digit number), the script will post the GenBank accession number directly to that iNaturalist observation without looking for the "Accession Number" observation field. The script also supports the "FUNDIS Tag Number" observation field as an alternative to the "Accession Number" observation field.
