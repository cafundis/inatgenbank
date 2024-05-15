<?php
// This script is dual licensed under the MIT License and the CC0 License.
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'max_execution_time', 900 );

include 'conf.php';

$useragent = 'iNatGenBank/1.0';
$inatapi = 'https://api.inaturalist.org/v1/';
$errors = [];
$updateResults = [];

function getAccessionNumbers( $fileData ) {
	$accessionNumbers = [];
	$observations = explode( "\n", $fileData );
	$x = 0;
	foreach( $observations as $observation ) {
		if ( preg_match( '/(\w+\d+)\t([\w\d\-]+)\t.*/', $observation, $matches ) && $x < 100 ) {
			$genbank = $matches[1];
			$sequenceid = $matches[2];
			$accessionNumbers[$sequenceid] = $genbank;
			$x++;
		}
	}
	if ( $x == 100 ) {
		$errors[] = 'Maximum number of records exceeded.';
	}
	return $accessionNumbers;
}

function make_curl_request( $url = null, $token = null, $postData = null ) {
	global $useragent, $errors;
	$curl = curl_init();
    if ( $curl && $url ) {
    	if ( $postData ) {
			$curlheaders = array(
				'Cache-Control: no-cache',
				'Content-Type: application/json',
				'Content-Length: ' . strlen( $postData ),
				'Accept: application/json'
			);
			if ( $token ) {
				$curlheaders[] = "Authorization: Bearer " . $token;
			}
			curl_setopt( $curl, CURLOPT_HTTPHEADER, $curlheaders );
        	curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'POST' );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $postData );
		}
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_USERAGENT, $useragent );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        $out = curl_exec( $curl );
		if ( $out ) {
        	$object = json_decode( $out );
        	if ( $object ) {
        		return json_decode( json_encode( $object ), true );
        	} else {
        		$errors[] = 'API request failed. ' . curl_error( $curl );
        		return null;
        	}
        } else {
        	$errors[] = 'API request failed. ' . curl_error( $curl );
        	return null;
        }
    } else {
    	$errors[] = 'Curl initialization failed. ' . curl_error( $curl );
        return null;
    }
}

function iNat_auth_request( $app_id, $app_secret, $username, $password, $url = 'https://www.inaturalist.org/oauth/token' ) {
	global $useragent, $errors;
    $curl = curl_init();
    $payload = array( 'client_id' => $app_id, 'client_secret' => $app_secret, 'grant_type' => "password", 'username' => $username, 'password' => $password );
    if ( $curl ) {
        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_USERAGENT, $useragent );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $payload );
        $out = curl_exec( $curl );
        if ( $out ) {
        	$object = json_decode( $out );
        	if ( $object ) {
        		return json_decode( json_encode( $object ), true );
        	} else {
        		$errors[] = 'API request failed. ' . curl_error( $curl );
        		return null;
        	}
        } else {
        	$errors[] = 'API request failed. ' . curl_error( $curl );
        	return null;
        }
    } else {
    	$errors[] = 'Curl initialization failed. ' . curl_error( $curl );
        return null;
    }
}

// Post GenBank acccession number
function post_genbank( $observationid, $genbank, $token ) {
	global $inatapi, $errors;
	$postData['observation_field_value'] = [];
	$postData['observation_field_value']['observation_id'] = intval( $observationid );
	$postData['observation_field_value']['value'] = $genbank;
	$postData['observation_field_value']['observation_field_id'] = 7555;
	$postData = json_encode( $postData );
	$url = $inatapi . 'observation_field_values';
	$response = make_curl_request( $url, $token, $postData );
	sleep( 1 );
	if ( $response ) {
		if ( isset( $response['error'] ) ) {
			$errors[] = 'Accession number could not be added for observation <a href="https://www.inaturalist.org/observations/' . $observationid . '">' . $observationid . "</a>. The owner may have this permission restricted.";
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}

// See if form was submitted.
if ( $_FILES && isset( $_FILES['accessionreport'] ) ) {
	// File size sanity check
	if ( $_FILES['accessionreport']['size'] < 10000 ) {
		$fileData = file_get_contents( $_FILES['accessionreport']['tmp_name'] );
		$accessionNumbers = getAccessionNumbers( $fileData );
		if ( $accessionNumbers ) {
			// Get authentication token
			$response = iNat_auth_request( $app_id, $app_secret, $username, $password );
			if ( $response && isset( $response['access_token'] ) ) {
				$token = $response['access_token'];
				foreach ( $accessionNumbers as $sequenceid => $genbank ) {
					// Get iNat observation ID
					$url = $inatapi . 'observations?field%3AAccession+Number=' . $sequenceid;
					$inatdata = make_curl_request( $url );
					sleep( 1 );
					if ( $inatdata
						&& isset( $inatdata['results'] )
						&& isset( $inatdata['results'][0] )
						&& isset( $inatdata['results'][0]['id'] )
					) {
						$observationid = $inatdata['results'][0]['id'];
						$updateResults[$sequenceid] = post_genbank( $observationid, $genbank, $token );
					} else {
						$url = $inatapi . 'observations?field%3ABOLD+ID=' . $sequenceid;
						$inatdata = make_curl_request( $url );
						sleep( 1 );
						if ( $inatdata
							&& isset( $inatdata['results'] )
							&& isset( $inatdata['results'][0] )
							&& isset( $inatdata['results'][0]['id'] )
						) {
							$observationid = $inatdata['results'][0]['id'];
							$updateResults[$sequenceid] = post_genbank( $observationid, $genbank, $token );
						} else {
							$updateResults[$sequenceid] = false;
							$errors[] = 'No observation found for ' . $sequenceid . '.';
						}
					}
				}
			} else {
				$errors[] = 'iNaturalist authentication failed.';
			}
		} else {
			$errors[] = 'No accession numbers found in input file.';
		}
	} else {
		$errors[] = 'AccessionReport file is too large.';
	}
}
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Language" content="en-us">
	<title>iNatGenBank</title>

<style type="text/css">
body {
	font-family: "Trebuchet MS", Verdana, sans-serif;
	color:#777777;
	background: #FFFFFF;
	}
#content {
	margin: 2em;
	}
#results {
	margin: 1em 0;
	}
#errors {
	margin: 1em 0;
	color: #FF6666;
	font-weight: bold;
	}
</style>
<script src="./jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function () {
    $("#form1").submit(function () {
        $(".submitbtn").attr("disabled", true);
        return true;
    });
});
</script>
</head>
<body>
<div id="content">
<?php
if ( $updateResults ) {
	print( '<p id="results">' );
	print( '<b>Results:</b><br/>' );
	foreach( $updateResults as $sequenceid => $result ) {
		if ( $result ) {
			print( '&nbsp;&nbsp;&nbsp;' . $sequenceid . ': <span style="color:#228B22;">Successfully updated</span><br/>' );
		} else {
			print( '&nbsp;&nbsp;&nbsp;' . $sequenceid . ': Failed to update<br/>' );
		}
	}
	print( '</p>' );
}
if ( $errors ) {
	print( '<p id="errors">' );
	print( 'Errors:<br/>' );
	foreach ( $errors as $error ) {
		print( '&nbsp;&nbsp;&nbsp;' . $error . '<br/>' );
	}
	print( '</p>' );
}
?>
<form id="form1" action="inatgenbank.php" method="post" enctype="multipart/form-data">
<p>Upload the AccessionReport.tsv file supplied by GenBank. Processing may take several minutes.</p>
<p>
	<input type="file" id="accessionreport" name="accessionreport" />
</p>
<input class="submitbtn" type="submit" />
</form>
</body>
</html>
