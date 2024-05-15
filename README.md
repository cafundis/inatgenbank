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
