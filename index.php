<?php
/**
 * Project : qui_possede_les_medias
 * File : index.php
 * @author  Pierre-Alexandre RACINE <patcha.dev at{@} gmail dot[.] com>
 * @copyright Copyright (c) 2020, Pierre-Alexandre RACINE <patcha.dev at{@} gmail dot[.] com>
 * @license http://www.gnu.org/licenses/lgpl.html
 * @date 17/04/2020
 * @link
 */

// Préparons notre curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Récupérons les entités :
curl_setopt($ch, CURLOPT_URL, 'https://raw.githubusercontent.com/mdiplo/Medias_francais/master/medias_francais.tsv');
$entites = curl_exec($ch);
// Récupérons les liens entre ces entités :
curl_setopt($ch, CURLOPT_URL, 'https://raw.githubusercontent.com/mdiplo/Medias_francais/master/relations_medias_francais.tsv');
$liens = curl_exec($ch);

// Fin des requêtes
curl_close($ch);

echo $liens;