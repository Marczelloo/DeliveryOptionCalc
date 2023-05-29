<!DOCTYPE html>
<html lang="PL-pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Delivery Option Calculator </title>
</head>
<body>
    <div class="container">
        <form method="post">
            <input type="number" name="szerokosc" placeholder="szerokosc [mm]">
            <input type="number" name="wysokosc" placeholder="wysokosc [mm]">
            <input type="number" name="ilosc" placeholder="ilosc">
            <input type="number" name="waga" placeholder="waga [kg]">
            <input type="submit" value="Oblicz">
        </form>
        <div class="result">
            <p>
                <?php
                    $calc = new DeliveryOptionCalc($_POST['szerokosc'], $_POST['wysokosc'], $_POST['waga'], $_POST['ilosc']);
                    echo $calc->handleDeliveryOption();
                ?>
            </p>
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

    if ($tireCount == 1 && $this->szerokosc <= 800 && $this->wysokosc <= 800 && $this->dlugosc <= 800) {
        // DPD Package
        return "Dostawa: DPD Liczba opon: $tireCount Liczba paczek: $tireCount Waga paczki: ". $this->waga. "Waga calkowita: ". $this->waga * $tireCount;
    } else {
        // Pallet Options
        $pallets = [
            ['name' => '1/2 Euro Pallet', 'width' => 800, 'length' => 600, 'height' => 1900, 'weight' => 25],
            ['name' => 'Euro Pallet', 'width' => 800, 'length' => 1200, 'height' => 1900, 'weight' => 30],
            ['name' => '160x90 Pallet', 'width' => 900, 'length' => 1600, 'height' => 1900, 'weight' => 35],
            ['name' => '120x120 Pallet', 'width' => 1200, 'length' => 1200, 'height' => 1900, 'weight' => 40],
            ['name' => '120x170 Pallet', 'width' => 1200, 'length' => 1700, 'height' => 1900, 'weight' => 45]
        ];

        $validPallets = [];

        foreach ($pallets as $pallet) {
            if ( $pallet['width'] >= $this->szerokosc && $pallet['length'] >= $this->dlugosc && $pallet['height'] >= $this->wysokosc ) {
                $validPallets[] = $pallet;
            }
        }

        if (count($validPallets) > 0) {
            usort($validPallets, function ($a, $b) {
                // Assuming the price order is from cheapest to most expensive
                $priceOrder = ['1/2 Euro Pallet', 'Euro Pallet', '160x90 Pallet', '120x120 Pallet', '120x170 Pallet'];
                $aIndex = array_search($a['name'], $priceOrder);
                $bIndex = array_search($b['name'], $priceOrder);
                return $aIndex - $bIndex;
            });

            $bestPallet = $validPallets[0];
            $palletName = $bestPallet['name'];
            $tiresOnPallet = $this->countTiresOnPallet($bestPallet['width'], $bestPallet['length'], $bestPallet['height']);
            $palletCount = ceil($tireCount / $tiresOnPallet);
            $totalPalletWeight = $this->waga * $tiresOnPallet;
            $totalWeight = $this->waga * $tireCount;
            $lastPalletTireCount = $tiresOnPallet * $palletCount - $tireCount;
            $lastPalletWeight = $lastPalletTireCount * $this->waga;

            if($palletCount == 1){
                return "Dostawa: $palletName | Liczba opon: $tireCount | Max liczba opon na palecie: $tiresOnPallet | Liczba palet: $palletCount | Max waga palety: $totalPalletWeight kg | Całkowita waga: $totalWeight kg";
            }
            else
            {
                return "Dostawa $palletName | Liczba opon $tireCount | Max liczba opon na palecie: $tiresOnPallet | Liczba opon na ostatniej palecie: $lastPalletTireCount | Liczba palet: $palletCount | Max waga palety: $totalPalletWeight kg | Waga ostatniej palety $lastPalletWeight | Całkowita waga: $totalWeight kg";
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


    // public function handleDeliveryOptionTest(){
    //     if($this->waga * $this->ilosc > 30)
    //     {
    //         if(
    //             ($this->szerokosc <= 800 && $this->dlugosc <= 600 && $this->wysokosc <= 1900) ||
    //             ($this->szerokosc <= 600 && $this->dlugosc <= 800 && $this->wysokosc <= 1900) ||
    //             ($this->szerokosc <= 1900 && $this->dlugosc <= 800 && $this->wysokosc <= 600) 
    //         )
    //         {
    //             return $this->ONE_HALF_Pallet();
    //         }
    //     } 
    //     else 
    //     {
    //         if($this->dlugosc <= 800 && $this->szerokosc <= 800 && $this->wysokosc <= 800 && $this->waga <= 30)
    //         {
    //             return $this->DPD_Delivery();
    //         }
    //     }   
    // }

    // private function pallet_size_hanlder($pallet_width, $pallet_lenght, $pallet_height){
    //     if($this->ilosc > 1)
    //     {
    //         $ilosc_opon = 0;
    //         for($i = $this->ilosc; $i >= 0; $i--){
    //             if($this->szerokosc * $i <= $pallet_width && $this->dlugosc <= $pallet_lenght && $this->wysokosc <= $pallet_height)
    //             {
    //                 $ilosc_opon += $i;
    //                 for($j = $this->ilosc; $j >= 0; $j--){
    //                     if($this->wysokosc * $i > $pallet_height)
    //                     {
    //                         if(
    //                             ($this->dlugosc * $j < $pallet_width && $this->szerokosc < $pallet_width - $this->szerokosc) ||
    //                             ($this->dlugosc * $j < $pallet_lenght - $this->dlugosc)
    //                         )
    //                         {
    //                             $ilosc_opon += $j;
    //                             break;
    //                         }
    //                     }
    //                     else if($this->wysokosc * $j < $pallet_height - $this->wysokosc)
    //                     {
    //                         $ilosc_opon += $ilosc_opon * $j;
    //                         break;
    //                     }
    //                 }
    //                 break;
    //             }
    //         }
    //     }
    //     else
    //     {
    //         $ilosc_opon = 1;
    //     }

    //     return $ilosc_opon;
    // }

    // private function DPD_Delivery(){
    //     $text = "Dostawa: DPD\n" . "Ilosc paczek: ".$this->ilosc."\n" . "Waga razem: ".$this->ilosc * $this->waga;
    //     return $text;
    // }

    // private function ONE_HALF_Pallet(){
    //     $ilosc_opon[0] = $this->pallet_size_hanlder(800, 600, 1900);
    //     echo $ilosc_opon[0]."<br>";
    //     $ilosc_opon[1] = $this->pallet_size_hanlder(600, 1900, 800);
    //     echo $ilosc_opon[1]."<br>";
    //     $ilosc_opon[2] = $this->pallet_size_hanlder(1900, 800, 600);
    //     echo $ilosc_opon[2]."<br>";


    //     $best = max($ilosc_opon);

    //     $chat_ilosc[0] = $this->countTiresOnPallet(800, 600, 1900);
    //     $chat_ilosc[1] = $this->countTiresOnPallet(600, 1900, 800);
    //     $chat_ilosc[2] = $this->countTiresOnPallet(1900, 600, 800);
    //     $chat_best = max($chat_ilosc);

    
    //     $text = "Dostawa: 1/2 Euro Paleta " . " Ilosc palet: ".ceil($this->ilosc / $best) . " Ilosc opon na palecie: ".$best . " Waga platy: ".$this->waga * $best . " Waga razem: ". $this->waga * $this->ilosc;
    //     $text2 = "Dostawa 1/2 Euro Paleta " . " Ilosc palet: ".ceil($this->ilosc / $chat_best) . " Ilosc opon na palecie ".$chat_best . " Waga palety: ".$this->waga * $chat_best . "Waga razem: ". $this->waga * $this->ilosc;
    //     $txt = $text."<br>".$text2;
    //     return $txt;
    // }
}
?>