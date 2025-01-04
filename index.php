<?php
    include "_connexionBD.php";
    
    // Dans cette partie code, les resquetes SQL sont préparées pour obtenir des information necessaire pour l'affichage
    
    $reqTopRestaurants=$bd->prepare("SELECT r.id_ville, v.ville, p.pays, p.code, COUNT(r.id_ville) AS nbr_resto FROM restaurants AS r JOIN villes AS v ON r.id_ville=v.id_ville JOIN pays AS p ON v.code_pays=p.code GROUP BY r.id_ville ORDER BY nbr_resto DESC, v.ville LIMIT 10;");
    $reqTopRestaurants->execute();

    $reqTowns=$bd->prepare("SELECT id_ville, ville FROM villes;");
    $reqTowns->execute();

    // Verification de GET avant de l'inseret dans la requete de recherche des restaurants de la ville du formulaire

    if(isset($_GET["town_select"])){
        $town_select_cleaned= (int)$_GET["town_select"];
        $reqTown=$bd->prepare("SELECT * FROM villes WHERE id_ville=:id_town");
        $reqTown->bindvalue("id_town", $town_select_cleaned);
        $reqTown->execute();
        $town_result=$reqTown->fetch();

        if($town_result){
            $id_town_ok=$town_select_cleaned;

            $reqRestaurants=$bd->prepare("SELECT r.id_restaurant, r.nom, r.description, SUM(v.nombre*b.prix) AS total_rest FROM ventes AS v JOIN burgers AS b ON v.id_burger=b.id_burger JOIN commandes AS c ON v.id_commande=c.id_commande JOIN employes AS e ON c.id_employe=e.id_employe JOIN restaurants AS r ON e.id_restaurant=r.id_restaurant WHERE r.id_ville=:id_town AND r.ouvert=1 GROUP BY r.id_restaurant ORDER BY r.nom;");
            $reqRestaurants->bindvalue("id_town", $id_town_ok);
            $reqRestaurants->execute();

            $reqTownTotal=$bd->prepare("SELECT vi.ville, SUM(ve.nombre*b.prix) AS total_ville FROM ventes AS ve JOIN burgers AS b ON ve.id_burger=b.id_burger JOIN commandes AS c ON ve.id_commande=c.id_commande JOIN employes AS e ON c.id_employe=e.id_employe JOIN restaurants AS r ON e.id_restaurant=r.id_restaurant JOIN villes AS vi ON r.id_ville=vi.id_ville WHERE r.id_ville=:id_town AND r.ouvert=1 GROUP BY r.id_ville ORDER BY r.nom;");
            $reqTownTotal->bindvalue("id_town", $id_town_ok);
            $reqTownTotal->execute();
            $town_total=$reqTownTotal->fetch();
            $town_total_money=$town_total["total_ville"];
            $town_total_name=$town_total["ville"];


        }else {header("Location:index.php");}
    }else {$id_town_ok=NULL;}


?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Restaurants v1</title>
</head>
<body>
    <main>
        <div id="top_restaurants_container">
            <?php
                $ligne_nbr = 1;
                while($restaurants=$reqTopRestaurants->fetch()){
                    $id_town=$restaurants["id_ville"];
                    $town_name=$restaurants["ville"];
                    $country_name=$restaurants["pays"];
                    $country_code=$restaurants["code"];
                    $resto_nbr=$restaurants["nbr_resto"];

                    if($ligne_nbr>0 and $ligne_nbr<=3) {$bold_style="style='font-weight: bold;'";}else {$bold_style='';}

                    echo "<div class='top_resto_lines'><a class='resto_links' $bold_style href='index.php?town_select=$id_town'>$ligne_nbr $town_name : <img src='flags/$country_code.webp' style='width: 25px;'></a> : $country_name : ";
                    for ($i=0; $i < $resto_nbr; $i++) { 
                        echo "<img src='icones/restaurant.png'  style='width: 35px;'>";
                    }
                    
                    echo "</div>";
                    
                    $ligne_nbr++;
                }
            ?>
        </div>
        <div id="town_filter_container">
            <form action="index.php" method="get">
                <select name="town_select" id="town_select">
                    <?php 
                        while($towns=$reqTowns->fetch()){
                            $id_town=$towns["id_ville"];
                            $town_names=$towns["ville"];

                            echo "<option value='$id_town'>$town_names</option>";
                        }
                    ?>
                </select>
                <input type="submit" value="Voir les restaurants">
            </form>
            <?php
                if(isset($id_town_ok)){
                    echo "<div id='town_resto_money_container'>";
                        while($town_restaurants=$reqRestaurants->fetch()){
                            $resto_name=$town_restaurants["nom"];
                            $resto_description=$town_restaurants["description"];
                            $total_value_rest=$town_restaurants["total_rest"];

                            echo "<p><b>$resto_name</b>($resto_description)<br>$total_value_rest €</p>";
                        }
                    echo "<p>Le total des ventes pour $town_total_name est de : $town_total_money €</p>";
                    
                    echo "</div>";
                }
            ?>
        </div>
    </main>
</body>
</html>