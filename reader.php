<?php $filename = $_GET["files"]; ?>
<head>
	<title>Nagios Tool: Windows Service Selector</title>
<style>
.TFtable{
	width:100%; 
	border-collapse:collapse; 
}
.TFtable td{ 
	padding:7px; border:#4e95f4 1px solid;
}
.TFtable tr{ 
	background: #b8d1f3;
}
.TFtable tr:nth-child(odd){ 
	background: #b8d1f3;
}
.TFtable tr:nth-child(even){
	background: #dae5f4;
}

pre code {
	background-color: #eee;
	border: 1px solid #999;
	display: block;
	padding: 20px;
}
</style>
</head>
<body>
<h2>Nagios Tool: Windows Service Selector</h2>

<form action="reader.php" method="get">
<?php
$dir = $_GET["dir"];

$files = scandir($dir);

// Prepare the select box to echo


if ($handle = opendir('./ReaderFiles/')) {
	echo "<select name=\"files\">";
	echo "<option value=\"\">Select a server</option>";
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
			echo "<option value=\"$entry\">$entry</option>";
        }
    }
	echo "</select>";
    closedir($handle);
}
?>
<input type="Submit">
</form>

Enter Custom Servername Here: <input type=text name=customServer id=customServer><BR>
<i>You will need to uncheck and recheck any boxes done prior to entering a custom hostname.</i>
<BR><BR>

<table border=1 class="TFtable">
	<tr>
		<td><B>Use</B></td>
		<td><B>Servername</B></td>
		<td><B>StartType</B></td>
		<td><B>DisplayName</B></td>
		<td><B>Name</B></td>
		<td><B>Status</B></td>
	</tr><?php
// Function used later to remove unused lines in CSV.
function containsWord($str, $word){
	return !!preg_match('#\\b' . preg_quote($word, '#') . '\\b#i', $str);
}

$file = fopen("./ReaderFiles/$filename", "r") or exit("Unable to open file!");
//Create the Array
$csvPHPArray = array("Service Name"=>"Service Description");

while(!feof($file)){
	$currentLine = fgets($file);
	$currentLine = str_replace("\r", '', $currentLine);
	$currentLine = str_replace("\n", '', $currentLine);
	$currentLine = str_replace("(", '', $currentLine);
	$currentLine = str_replace(")", '', $currentLine);
	if (containsWord($currentLine,"TYPE Selected") === true){
		// Ignoring the PowerShell generated Line
	} elseif (containsWord($currentLine,"DisplayName") === true){
		// Ignoring the Header Row
	} elseif ($currentLine === "") {
		// Ignoring empty lines
	} else {
		$csvServices = explode(',',$currentLine);
		// Strip Quotes from Strings
		$csvServices[0] = str_replace('"', '', $csvServices[0]);
		$csvServices[1] = str_replace('"', '', $csvServices[1]);
		$csvServices[2] = str_replace('"', '', $csvServices[2]);
		$csvServices[3] = str_replace('"', '', $csvServices[3]);
		$csvServices[4] = str_replace('"', '', $csvServices[4]);
		$csvPHPArray = array_merge($csvPHPArray, array($csvServices[1]=>$csvServices[0]));
        echo '<tr>'."\r\n\t\t".'<td><input type=checkbox onchange="updateCodeBlock('.str_replace(' ', '', $csvServices[1]).')" name="'.$csvServices[1].'" id="'.str_replace(' ', '', $csvServices[1]).'"></td>'."\r\n";
		// 
		// Servername then set variable for later passing to JavaScript.
		echo "\t\t<td>" .$csvServices[3]. "</td>\r\n";
		$JSPassServerName = $csvServices[3];
		// StartType
		echo "\t\t<td>" .$csvServices[4]. "</td>\r\n";
		// DisplayName
		echo "\t\t<td>" .$csvServices[0]. "</td>\r\n";
		// Name
		echo "\t\t<td>" .$csvServices[1]. "</td>\r\n";
		// Status
		echo "\t\t<td>" .$csvServices[2]. "</td>\r\n\t</tr>";
	}
}
fclose($file);
?>
</table>

<BR>

<script type="text/javascript">
// PHP dump of the services into an array for reading later.
var servicesArray = <?php echo json_encode($csvPHPArray); ?>;

var csvHostname = "<?php echo $JSPassServerName; ?>";

function updateCodeBlock(arrayUpdate) {
	// arrayUpdate.id for the id tag of the checkbox.
	// arrayUpdate.name for the name tag of the checkbox.
	// arrayUpdate.checked to see if the checkbox has been checked or unchecked.
	if (arrayUpdate.checked == true) {
		// Create the service in CODE with id of "codeBlock"
		var para = document.createElement("P");						// Creates a new <p> element.
		para.setAttribute("id", arrayUpdate.id + "paragraph");		// Set the id tag (appends paragraph to not interfere with other id tags.)
		var t = document.createTextNode("#\n# " + arrayUpdate.name + " : " + servicesArray[arrayUpdate.name] + "\n#\n");			// Creating the beginning of the text in paragraph.
		para.appendChild(t);										// Append the text to <p>
		document.getElementById("codeBlock").appendChild(para);		// Append <p> to <div> with id="codeBlock"
		// Append the rest of the code to the paragraph.
		document.getElementById(arrayUpdate.id + "paragraph").innerHTML+="define service{\n";
		document.getElementById(arrayUpdate.id + "paragraph").innerHTML+="\tUse\t\t\tgeneric-service\n";
		document.getElementById(arrayUpdate.id + "paragraph").innerHTML+="\thost_name\t\t";
		// Check to see if a custom servername has been entered.
		var customSrv = document.getElementById("customServer").value;
		if (customSrv.length < 1) {
			document.getElementById(arrayUpdate.id + "paragraph").innerHTML+=csvHostname;
		} else {
			document.getElementById(arrayUpdate.id + "paragraph").innerHTML+=customSrv;
		}
		document.getElementById(arrayUpdate.id + "paragraph").innerHTML+="\n";
		document.getElementById(arrayUpdate.id + "paragraph").innerHTML+="\tservice_description\t" + servicesArray[arrayUpdate.name] + "\n";
		// fixMe variable repairs the issues in Nagios with $ symbols.
		var fixMe = arrayUpdate.name;
		fixMe = fixMe.replace('$','\\$$$$');
		document.getElementById(arrayUpdate.id + "paragraph").innerHTML+="\tcheck_command\t\tcheck_nt!SERVICESTATE!-d SHOWALL -l " + fixMe + "\n";
		document.getElementById(arrayUpdate.id + "paragraph").innerHTML+="}\n";
		
	} else {
		// Remove service created above.
		var oldElement = document.getElementById(arrayUpdate.id + "paragraph");
		oldElement.remove();
	}
}
</script>

<H3>Code will show up below.</H3>
<PRE>
	<CODE id=codeBlock></CODE>
</PRE>
</body>
