<?php
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->prepare("UPDATE users SET profile_photo = '/assets/images/sai_rohan_portrait.jpg', blood_group = 'A1+ve', father_name = 'Lingadhurai. S', mother_name = 'Patturani. L', phone = '8939319656', alternate_phone = '9841882666', address = 'J.K. builders 2nd floor, Rangangar 1st main, Old Perungalathur, Chennai.' WHERE id = 10 OR member_id = 'sairohan2012.koms'");
$stmt->execute();
echo "Sai Rohan user details updated successfully.\n";
