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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once './graph.php';

$mes_options = array(
    'CSS' => array(
        'id_graphe'=>'blocGrapheMedia',
        'classes_graphe'    => 'classe1 classe2 clase3'
    ),
    'dimensions' => array(
        'largeur_graphe' => '800px',
        'hauteur_graphe' => '800px',
    ),
    'page_web_complete' => true
);

echo recupererCodeGraphe($mes_options);