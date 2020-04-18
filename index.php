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

$donnees = recupererDonnesNettoyees();
$options = array();

echo afficherGraphe($donnees[0], $donnees[1], $donnees[2]);

function afficherGraphe(
    array &$tableauIdNom = array(),
    array &$tableauNomId = array(),
    array &$relations    = array(),
    array &$options      = array()
){
    $codeHTML = '';
    $codeHTML .= genererEnTete();

    $codeHTML .= genererBloc();

    $codeHTML .= genererJavascript($tableauIdNom, $tableauNomId, $relations);

    $codeHTML .= genererPiedDePage();

    return $codeHTML;
}

function genererPiedDePage(){
    return '
</body>
</html>';
}

function genererJavascript(
    array &$tableauIdNom = array(),
    array &$tableauNomId = array(),
    array &$relations    = array()
){
    $codeJS ='
    <script type="text/javascript">
        // Le tableau des nœuds.
        var nodes = new vis.DataSet([';
    foreach ($tableauIdNom as $id=>$entite){
        $codeJS .= '
            {id: ' . $id . ', label: \'' . $entite . '\'},';
    }
    $codeJS .= '
            ]);
            
        // Le tableau des arêtes.
        var edges = new vis.DataSet([';
    foreach ($relations as $relation) {
        //var_dump($relation);
        $codeJS .= '
            {from: ' . $relation['origine'] . ', to: ' . $relation['cible'] . '},';
    }
    $codeJS .= '
            ]);
';



    $codeJS .= '
        // create a network
        var container = document.getElementById(\'mynetwork\');
        var data = {
            nodes: nodes,
            edges: edges
            };
        var options = {
            layout: {
                improvedLayout: false
                },
            physics: {
                adaptiveTimestep: true,
                barnesHut: {
                    springConstant: 0.04,
                    springLength: 95
                    },
                stabilization: {
                    iterations: 400
                    }
                },
            };
        var network = new vis.Network(container, data, options);
    </script>';

    return $codeJS;
}

function genererBloc(){
    return '<div id="mynetwork"></div>';
}

function genererEnTete(){
    return '<!doctype html>
<html>
<head>
  <title>Qui possède les médias français ?</title>

    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

  <style type="text/css">
        #mynetwork {
        width: 1000px;
      height: 800px;
      border: 1px solid lightgray;
    }
</style>
</head>
<body>';
}


/**
 * Va lire les tableurs de données sur github puis filtre, rage et trie les informations dont on se servira.
 * @return array[] array( array(id1:nom1, id2:nom2,...), array(nom1:id1, nom2:id2,...) array( array(origine=>id1, cible=>id2), array(origine=>id2, cible=>idY) )
 */
function recupererDonnesNettoyees(){
    // D'abord les entités.
    $idEntiteNomEntite=array();
    $nomEntiteIdEntite=array();
    $entites = file_get_contents('https://raw.githubusercontent.com/mdiplo/Medias_francais/master/medias_francais.tsv');
    $entites = explode("\n", $entites); // Suppression de la première ligne.
    array_shift($entites);
    foreach ($entites as $entite) {
        if($entite==''){
            break;
        }
        $entite = explode("\t", $entite);
        //var_dump($entite);
        $idEntiteNomEntite[$entite[0]] = $entite[1];
        $nomEntiteIdEntite[$entite[1]] = $entite[0];
    }

    // Puis les relations.
    $relations=array();
    $liensVrac = file_get_contents('https://raw.githubusercontent.com/mdiplo/Medias_francais/master/relations_medias_francais.tsv');
    $liensVrac = explode("\n", $liensVrac); // Suppression de la première ligne.
    array_shift($liensVrac);
    foreach ($liensVrac as $lien) {
        if ($lien==''){
            break;
        }
        $lien = explode("\t", $lien);
        if( isset($nomEntiteIdEntite[$lien[3]])){
            $relation=array(
                'origine'   => $lien[0],
                'cible'     => $nomEntiteIdEntite[$lien[3]]
            );
            array_push($relations, $relation);
        }
    }

    return array($idEntiteNomEntite, $nomEntiteIdEntite, $relations);
}