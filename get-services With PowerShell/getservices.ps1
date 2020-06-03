$Computers = get-content .\serverlist.txt
$Service = "sql"

foreach ($computer in $computers) {
   $Servicestatus = get-service -name * -ComputerName $computer
   $Servicestatus | select-object Displayname,Name,Status,MachineName,Starttype | Sort-Object Starttype,Status,Displayname,Name | Export-Csv -path .\services\$computer.csv
}