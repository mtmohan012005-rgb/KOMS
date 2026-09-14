$adb = "C:\Users\mohan\AppData\Local\Android\Sdk\platform-tools\adb.exe"
$outDir = "C:\Users\mohan\.gemini\antigravity-ide\brain\ff045915-e8e5-48ad-b4c8-4ccce1091d7b"

$students = @(
    @{ id = "01"; name = "sairohan"; user = "sairohan2012.koms"; pass = "20.10.2012"; display = "Sai Rohan L" },
    @{ id = "02"; name = "guhan"; user = "dguhan2015.koms"; pass = "25.09.2015"; display = "Guhan D" },
    @{ id = "03"; name = "harshini"; user = "harshini2012.koms"; pass = "31.07.2012"; display = "Harshini S" },
    @{ id = "04"; name = "varshini"; user = "svarshini2012.koms"; pass = "30.10.2012"; display = "Varshini S" },
    @{ id = "05"; name = "prathyuminan"; user = "gpprathyuminan2020.koms"; pass = "25.09.2020"; display = "Prathyuminan G P" },
    @{ id = "06"; name = "harshitha"; user = "rharshitha2016.koms"; pass = "04.08.2016"; display = "Harshitha R" },
    @{ id = "07"; name = "darshitha"; user = "rdarshitha2016.koms"; pass = "04.08.2016"; display = "Darshitha R" },
    @{ id = "08"; name = "niranjanasri"; user = "mpniranjanasri2019.koms"; pass = "30.10.2019"; display = "Niranjana Sri M P" },
    @{ id = "09"; name = "krishcharan"; user = "mkrishcharan2017.koms"; pass = "08.08.2017"; display = "Krish Charan M" },
    @{ id = "10"; name = "thejasri"; user = "thejasri2019.koms"; pass = "02.05.2019"; display = "Thejasri A" },
    @{ id = "11"; name = "karunesh"; user = "karunesh2014.koms"; pass = "20.08.2014"; display = "Karunesh M" },
    @{ id = "12"; name = "pragatheeshwaran"; user = "vpragatheeshwaran2016.koms"; pass = "25.02.2016"; display = "Pragatheeshwaran V" },
    @{ id = "13"; name = "advick"; user = "advick2016.koms"; pass = "02.07.2016"; display = "Advick A" }
)

Write-Host "Starting batch login and screenshot capture for all 13 students..."

foreach ($s in $students) {
    Write-Host "[$($s.id)/13] Logging in: $($s.display) ($($s.user))..."
    & $adb -s RZ8M43AH54Y shell pm clear com.koms.app | Out-Null
    Start-Sleep -Milliseconds 600
    
    # Ensure port forward is active
    & $adb -s RZ8M43AH54Y reverse tcp:8080 tcp:8080 | Out-Null
    
    & $adb -s RZ8M43AH54Y shell am start -n com.koms.app/.ui.auth.LoginActivity --es auto_user "$($s.user)" --es auto_pass "$($s.pass)" | Out-Null
    Start-Sleep -Seconds 3
    
    $remotePath = "/sdcard/student_$($s.id)_$($s.name).png"
    $localPath = "$outDir\student_$($s.id)_$($s.name).png"
    
    & $adb -s RZ8M43AH54Y shell screencap -p $remotePath
    & $adb -s RZ8M43AH54Y pull $remotePath $localPath | Out-Null
    Write-Host "  -> Saved: student_$($s.id)_$($s.name).png"
}

Write-Host "`nAll 13 student mobile logins and line-by-line screenshots captured successfully!"
