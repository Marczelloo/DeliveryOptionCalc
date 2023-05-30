<!DOCTYPE html>
<html lang="PL-pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;300;500;700;900&display=swap" rel="stylesheet">
    <title> Delivery Option Calculator </title>
</head>
<body>
    <div class="container">
        <form method="post">
            <h1> Podaj wymiary opony </h1>
            <input type="number" name="szerokosc" placeholder="szerokosc [mm]">
            <input type="number" name="wysokosc" placeholder="wysokosc [mm]">
            <input type="number" name="ilosc" placeholder="ilosc">
            <input type="number" name="waga" placeholder="waga [kg]">
            <input type="submit" name="submit" value="Oblicz">
        </form>
        <div class="result">
            <?php
            if(isset($_POST["submit"]))
            {
                $calc = new DeliveryOptionCalc($_POST['szerokosc'], $_POST['wysokosc'], $_POST['waga'], $_POST['ilosc']);
                $delivery = $calc->handleDeliveryOption();
                    
                echo "<h1>".$delivery['dostawa']."</h1>";

                echo "<ul>";
                foreach($delivery as $d => $k){
                    echo "<li>".$d.": ".$k."</li>";
                }
                echo "</ul>";
            }
            ?>
        </div>
    </div>
</body>
</html>
<?php

class DeliveryOptionCalc{
    private $szerokosc; // mm
    private $wysokosc; // mm
    private $dlugosc; // mm |
    private $waga; // kg
    private $ilosc;

    public function __construct($szerokosc, $wysokosc, $waga, $ilosc){
        
        $this->szerokosc = $szerokosc;
        $this->dlugosc = $wysokosc;
        $this->wysokosc = $wysokosc;
        $this->waga = $waga;
        $this->ilosc = $ilosc;
    }

    public function handleDeliveryOption(){
    $tireCount = $this->ilosc;

    if ($this->szerokosc <= 800 && $this->wysokosc <= 800 && $this->dlugosc <= 800) {
        // DPD Package 15zl za 
        $package = ['name' => 'DPD', 'width'=> 800, 'length'=> 800, 'height' => 800, 'price' => 15];
        $package_price = $this->ilosc * $package['price'];
    } 
    else
    {
        $package_price = null;
    }
        // Pallet Options
        $pallets = [
            ['name' => '1/2 Euro Pallet', 'width' => 800, 'length' => 600, 'height' => 1900, 'weight' => 25, 'price' => 80],
            ['name' => 'Euro Pallet', 'width' => 800, 'length' => 1200, 'height' => 1900, 'weight' => 30, 'price' => 120],
            ['name' => '160x90 Pallet', 'width' => 900, 'length' => 1600, 'height' => 1900, 'weight' => 35, 'price' => 145],
            ['name' => '120x120 Pallet', 'width' => 1200, 'length' => 1200, 'height' => 1900, 'weight' => 40, 'price' => 160],
            ['name' => '120x170 Pallet', 'width' => 1200, 'length' => 1700, 'height' => 1900, 'weight' => 45, 'price' => 240]
        ];

        $validPallets = [];

        foreach ($pallets as $pallet) {
            if ( $pallet['width'] >= $this->szerokosc && $pallet['length'] >= $this->dlugosc && $pallet['height'] >= $this->wysokosc ) {
                $validPallets[] = $pallet;
            }
        }

        if (count($validPallets) > 0) {
            foreach($validPallets as $p){
                $validPalletsCount[] = ['name' => $p['name'],
                'width' => $p['width'],
                'length' => $p['length'],
                'height' => $p['height'],
                'weight' => $p['weight'],
                'price' => $p['price'],
                'palletCount'=> ceil($tireCount / $this->countTiresOnPallet($p['width'], $p['length'], $p['height']))];
            }
            
            

            $palletCounts = array_column($validPalletsCount, 'palletCount');
            $minPalletCount = min($palletCounts);

            $palletCountCheck = array_filter($validPalletsCount, function($p) use ($minPalletCount){
                return $p['palletCount'] == $minPalletCount;
            });

            if (count($palletCountCheck) > 1) {
                usort($validPalletsCount, function($a, $b) {
                    if ($a['palletCount'] == $b['palletCount']) {
                        return $a['price'] - $b['price'];
                    }
                    return $a['palletCount'] - $b['palletCount'];
                });

                $bestPallet = $validPalletsCount[0];
            } else {
                foreach($validPalletsCount as $p){
                    if($p['palletCount'] == $minPalletCount){
                        $bestPallet = $p;
                    }
                }
            }

            $palletPrice = $bestPallet['palletCount'] * $bestPallet['price'];

            if($package_price < $palletPrice && $package_price != null){
                $bestDeliveryOption = $package;
            }
            else
            {
                $bestDeliveryOption = $bestPallet;
            }
        
    
            $deliveryName = $bestDeliveryOption['name'];
            $tiresOnPallet = $deliveryName == "DPD" ?  1 : $this->countTiresOnPallet($bestPallet['width'], $bestPallet['length'], $bestPallet['height']);
            $palletCount = $deliveryName  == "DPD" ? $tireCount : $bestDeliveryOption['palletCount'];
            $totalPalletWeight = $this->waga * $tiresOnPallet;
            $totalWeight = $this->waga * $tireCount;
            $lastPalletTireCount = $tireCount - $tiresOnPallet * $palletCount;
            $lastPalletWeight = $lastPalletTireCount * $this->waga;

            if($deliveryName == "DPD"){
                return [
                "dostawa" => $deliveryName,
                "cena" => $package_price,
                "liczba opon" => $tireCount,
                "liczba opon w paczce" => $tiresOnPallet,
                "liczba paczek" => $palletCount,
                "waga paczki" => (string)$totalPalletWeight." kg",
                "waga całkowita" => (string)$totalWeight." kg"
                ];
                // return "Dostawa: $deliveryName | Cena: $package_price | Liczba opon: $tireCount | Liczba opon w paczce: $tiresOnPallet | Liczba paczek: $palletCount | Waga paczki: $totalPalletWeight kg | Całkowita waga: $totalWeight kg";
            }
            else
            {
                if($palletCount == 1){
                    return [
                        "dostawa" => $deliveryName,
                        "cena" => $palletPrice,
                        "liczba opon" => $tireCount,
                        "max liczba opon na palecie" => $tiresOnPallet,
                        "liczba palet" => $palletCount,
                        "max waga palety" => (string)$totalPalletWeight." kg",
                        "waga całkowita" => (string)$totalWeight." kg"
                    ];
                    // return "Dostawa: $deliveryName | Cena: $palletPrice | Liczba opon: $tireCount | Max liczba opon na palecie: $tiresOnPallet | Liczba palet: $palletCount | Max waga palety: $totalPalletWeight kg | Całkowita waga: $totalWeight kg";
                }
                else
                {
                    return [
                        "dostawa" => $deliveryName,
                        "cena" => $palletPrice,
                        "liczba opon" => $tireCount,
                        "max liczba opon na palecie" => $tiresOnPallet,
                        "liczba opon na ostatniej palecie" => $lastPalletTireCount,
                        "liczba palet" => $palletCount,
                        "max waga palety" => (string)$totalPalletWeight." kg",
                        "waga ostatniej palety" => (string)$lastPalletWeight." kg",
                        "waga całkowita" => (string)$totalWeight." kg"
                    ];
                    //return "Dostawa $deliveryName | Cena: $palletPrice | Liczba opon $tireCount | Max liczba opon na palecie: $tiresOnPallet | Liczba opon na ostatniej palecie: $lastPalletTireCount | Liczba palet: $palletCount | Max waga palety: $totalPalletWeight kg | Waga ostatniej palety $lastPalletWeight | Całkowita waga: $totalWeight kg";
                } 
            }
            
        }
    

    return "Nie znaleziono odpowiedniego rodzaju przesyłki dla takiej opony!";
    
    }

    
    private function countTiresOnPallet($palletWidth, $palletLength, $palletHeight)
    {
        $tiresPerLayer = floor($palletWidth / $this->szerokosc) * floor($palletLength / $this->dlugosc);
        $totalLayers = floor($palletHeight / $this->wysokosc);
    
        $totalTireCount = $tiresPerLayer * $totalLayers;
    
        return $totalTireCount;
    }
}
?>