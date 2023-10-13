<?php

// MODELE EN  CONSOLE DU PROTOCOLE BASE SUR LES REPERTOIRES

// Définition des constantes
define("NOMBREPROCESSEUR", 4); // Nombre de processeurs
define("NOMBREBLOCK", 16); // Nombre de blocs de mémoire
define("TAILLEBLOCK", 4); // Taille d'un bloc en octets
define("TAILLECACHE", 8); // Taille du cache en blocs
define("INVALID", 0); // Etat invalide du cache
define("SHARED", 1); // Etat partagé du cache
define("EXCLUSIVE", 2); // Etat EXCLUSIF du cache

// Initialisation de la mémoire principale
$memoire = array();
for ($i = 0; $i < NOMBREBLOCK; $i++) {
    $memoire[$i] = array();
    for ($j = 0; $j < TAILLEBLOCK; $j++) {
        $memoire[$i][$j] = rand(0, 255); // Valeur aléatoire entre 0 et 255
    }
}

// Initialisation du répertoire
$repertoire = array();
for ($i = 0; $i < NOMBREBLOCK; $i++) {
    $repertoire[$i] = array();
    $repertoire[$i]["Etat"] = "Un"; // Etat non caché
    $repertoire[$i]["Partagé"] = array(); // Liste des processeurs partageant le bloc
}

// Initialisation des caches des processeurs
$caches = array();
for ($p = 0; $p < NOMBREPROCESSEUR; $p++) {
    $caches[$p] = array();
    for ($i = 0; $i < TAILLECACHE; $i++) {
        $caches[$p][$i] = array();
        $caches[$p][$i]["tag"] = -1; // Tag invalide
        $caches[$p][$i]["Etat"] = INVALID; // Etat invalide
        $caches[$p][$i]["data"] = array(); // Données vides
    }
}

// mAFONCTIONFonction pour lire une donnée à une adresse donnée par un processeur donné
function readDonnée ($processeur, $adresse) {
    global $memoire, $repertoire, $caches;
    // Calcul du numéro de bloc et du décalage dans le bloc
    $block = floor($adresse / TAILLEBLOCK);
    $offset = $adresse % TAILLEBLOCK;
    // Recherche du bloc dans le cache du processeur
    for ($i = 0; $i < TAILLECACHE; $i++) {
        if ($caches[$processeur][$i]["tag"] == $block) {
            // read  HIT: le bloc est présent dans le cache
            echo "Read HIT \n";
            // P$processeur lit la donnée à l'adresse $adresse depuis son cache\n";
            return $caches[$processeur][$i]["data"][$offset]; // Retourne la donnée lue dans le cache
        }
    }
    // readDonnée  MISS: le bloc n'est pas présent dans le cache
    echo "Read  MISS \n";
    //P$processeur demande le bloc $block à la mémoire\n";
    // Envoie une requête au répertoire pour obtenir le bloc
    switch ($repertoire[$block]["Etat"]) {
        case "Un": // Le bloc n'est pas caché par d'autres processeurs
            echo "Répertoire: Le block est UNCACHED \n";
            // Change l'état du répertoire à partagé et ajoute le processeur demandeur à la liste des partageurs
            $repertoire[$block]["Etat"] = "Sh";
            $repertoire[$block]["Partagé"][] = $processeur;
            // Envoie une réponse au processeur avec le bloc et l'état partagé
            echo "Répertoire: Le block à P$processeur  est SHARED \n";
            // Place le bloc dans le cache du processeur avec le tag et l'état correspondants
            $caches[$processeur][0]["tag"] = $block;
            $caches[$processeur][0]["Etat"] = SHARED;
            $caches[$processeur][0]["data"] = $memoire[$block];
            // Retourne la donnée lue dans le cache
            return $caches[$processeur][0]["data"][$offset];
        case "Sh": // Le bloc est partagé par d'autres processeurs
            echo "Répertoire: Le block est partagé par " . implode(", ", $repertoire[$block]["Partagé"]) . "\n";
            // Ajoute le processeur demandeur à la liste des partageurs
            $repertoire[$block]["Partagé"][] = $processeur;
            // Envoie une réponse au processeur avec le bloc et l'état partagé
            echo "Répertoire: Le block à P$processeur est SHARED \n";
            // Place le bloc dans le cache du processeur avec le tag et l'état correspondants
            $caches[$processeur][0]["tag"] = $block;
            $caches[$processeur][0]["Etat"] = SHARED;
            $caches[$processeur][0]["data"] = $memoire[$block];
            // Retourne la donnée lue dans le cache
            return $caches[$processeur][0]["data"][$offset];
        case "Ex": // Le bloc est EXCLUSIF par un autre processeur
            echo "Répertoire: Le block est EXCLUSIF par P" . $repertoire[$block]["Partagé"][0] . "\n";
            // Envoie une requête de rétrogradation au processeur EXCLUSIF
            echo "Répertoire: envoie une requête de rétrogradation à P" . $repertoire[$block]["Partagé"][0] . "\n";
            // Attend la réponse du processeur EXCLUSIF avec le bloc et l'état partagé
            echo "P" . $repertoire[$block]["Partagé"][0] . ": envoie une réponse de rétrogradation avec le block $block et SHARED \n";
            // Change l'état du répertoire à partagé et ajoute le processeur demandeur à la lise des partageurs
            $repertoire[$block]["Etat"] = "Sh";
            $repertoire[$block]["Partagé"][] = $processeur;
            // Envoie une réponse au processeur demandeur avec le bloc et l'état partagé
            echo "Répertoire: Le block à P$processeur est SHARED \n";
            // Place le bloc dans le cache du processeur avec le tag et l'état correspondants
            $caches[$processeur][0]["tag"] = $block;
            $caches[$processeur][0]["Etat"] = SHARED;
            $caches[$processeur][0]["data"] = $memoire[$block];
            // Retourne la donnée lue dans le cache
            return $caches[$processeur][0]["data"][$offset];
    }
}

// Fonction pour écrire une donnée à une adresse donnée par un processeur donné
function writeDonnée ($processeur, $adresse, $value) {
    global $memoire, $repertoire, $caches;
    // Calcul du numéro de bloc et du décalage dans le bloc
    $block = floor($adresse / TAILLEBLOCK);
    $offset = $adresse % TAILLEBLOCK;
    // Recherche du bloc dans le cache du processeur
    for ($i = 0; $i < TAILLECACHE; $i++) {
        if ($caches[$processeur][$i]["tag"] == $block) {
            // Cache hit: le bloc est présent dans le cache
            switch ($caches[$processeur][$i]["Etat"]) {
                case SHARED: // Le bloc est partagé avec d'autres processeurs
                    echo "Write HIT \n";
                    //: P$processeur écrit la donnée à l'adresse $adresse est SHARED \n";
                    // Envoie une requête au répertoire pour obtenir le bloc en modification
                    echo "P$processeur: envoie une requête de modification pour le block \n";
                    // Attend la réponse du répertoire avec le bloc et l'état EXCLUSIF
                    echo "Répertoire: envoie le block à P$processeur \n";
                    // Change l'état du cache à EXCLUSIF
                    $caches[$processeur][$i]["Etat"] = EXCLUSIVE;
                    // Ecrit la donnée dans le cache
                    $caches[$processeur][$i]["data"][$offset] = $value;
                    // Retourne la donnée écrite dans le cache
                    return $caches[$processeur][$i]["data"][$offset];
                case MODIFIE: // Le bloc est MODIFIE par le processeur et n'exite pas chez les autres.
                    echo "Write MISS \n";
                    //P$processeur écrit la donnée à l'adresse $adresse est MODIFIE\n";
                    // Ecrit la donnée dans le cache
                    $caches[$processeur][$i]["data"][$offset] = $value;
                    // Retourne la donnée écrite dans le cache
                    return $caches[$processeur][$i]["data"][$offset];
                    }
                }
            }
        // Cache miss: le bloc n'est pas présent dans le cache
        echo "Write MISS \n";
        //: P$processeur demande le bloc $block à la mémoire\n";
        // Envoie une requête au répertoire pour obtenir le bloc en modification
        echo "P$processeur: envoie une requête de modification pour le block \n";
        // Attend la réponse du répertoire avec le bloc et l'état MODIFIE
        echo "Répertoire: envoie le block à P$processeur est MODIFIE\n";
        // Place le bloc dans le cache du processeur avec le tag et l'état correspondants
        $caches[$processeur][0]["tag"] = $block;
        $caches[$processeur][0]["Etat"] = EXCLUSIVE;
        $caches[$processeur][0]["data"] = $memoire[$block];
        // Ecrit la donnée dans le cache
        $caches[$processeur][0]["data"][$offset] = $value;
        // Retourne la donnée écrite dans le cache
        return $caches[$processeur][0]["data"][$offset];
    }
                    
    // INTERFACE ET UTILISATION DU CODE
                    
    echo "PROTOCOLE BASE SUR LES REPERTOIRES (DIRECTORY)\n\n\n ";
    
    echo "Condition d'éxperimentation:\n";
    echo "      1. Processeur sont à 4 : P0 -- P1 -- P2 -- P3 ;\n";
    echo "      2. Les Contenues de la Memoire, la Cache et le repertoire sont aléatoires \n";
    echo "      3. Si vous souhaitez savoir l'état précédant de la memoire, les repertoires et les caches vous pouvez desactiver le commentaire y réfèrant dans le code;\n";
    echo "      4. Si vous entrez un string, il est automatiquement casté en un entier;\n";
    
    //echo " AFFICHER LA SITUATION INITIALE DE LA MEMOIRE ";           
    //print_r($memoire);
    echo "\n";
    echo "\n";
    // EXPERIMENTER LA LECTURE               
    echo "A. EXEPERIMENTER LA LECTURE ";
    echo "\n";
    $data1 = (int) readline('   Entrer le processeur: ' );
    $data2 = (int) readline("   Entrer Adresse: ");
    echo "\n";
    $data = readDonnée ($data1, $data2);
    echo "      La Donnée lue est : $data2\n";
    echo "\n";
    // EXPERIMENTER LA LECTURE
    echo "B. EXEPERIMENTER L'ECRITURE \n";
    $data3 = (int) readline('   Entrer le processeur: ') ;
    $data4 =  (int) readline('   Entrer l Adresse: ');
    $data5 = (int) readline('   Entrer la donnée: ');
    $data = writeDonnée ($data3, $data4, $data5);
    echo "      La Donnée écrite est : $data5\n";
    echo "\n";
    // les etats finales en memoire, en caches et dans le repertoire.
    // echo "Etat final de la mémoire:\n";
    // print_r($memoire);
    // echo "\n";             
    // echo "Etat final des caches:\n";
    // print_r($caches);
    // echo "\n";            
    // echo "Etat final du répertoire:\n";
    // print_r($repertoire);
    echo "\n";
    
    echo " Réalisé par @DavidAlse ";
    echo "\n";
    echo "\n";