<?php
$brands = $db->query("SELECT DISTINCT brand FROM vehicles ORDER BY brand ASC;");

echo '<datalist id="brand">';
while ($brand = $brands->fetch_assoc()) echo "<option value=\"{$brand['brand']}\">{$brand['brand']}</option>";
echo '</datalist>';
