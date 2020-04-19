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
$logos = recupererLogos();
$options = array();

echo afficherGraphe($donnees[0], $donnees[1], $donnees[2], $donnees[3], $options);

function afficherGraphe(
    array &$tableauIdNom = array(),
    array &$tableauNomId = array(),
    array &$typeEntites  = array(),
    array &$relations    = array(),
    array &$options      = array()
){
    $codeHTML = '';
    $codeHTML .= genererEnTete();

    $codeHTML .= genererBloc();

    $codeHTML .= genererJavascript($tableauIdNom, $tableauNomId, $typeEntites, $relations);

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
    array &$typeEntites  = array(),
    array &$relations    = array()
){
    $codeJS ='
    <script type="text/javascript">
        // Le tableau des nœuds.
        var nodes = new vis.DataSet([';
    foreach ($tableauIdNom as $id=>$entite){
        $codeJS .= '
            {id: ' . $id . ', label: \'' . $entite . '\', image: \'' . recupererLogos($entite) . '\', shape: \'image\'},';
    }
    $codeJS .= '
            ]);
            
        // Le tableau des arêtes.
        var edges = new vis.DataSet([';
    foreach ($relations as $relation) {
        $codeJS .= '
            {from: ' . $relation['origine'] . ', to: ' . $relation['cible'] . ', arrows:\'to\'},';
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


function assignerForme($idTypeEntite=''){
    $formes = array(
        '1'=> 'box',        // Personne physique
        '2'=> 'ellipse',    // Personne morale
        '3'=> 'ellipse',    // Média
    );
    if( isset($formes[$idTypeEntite]) ) {
        return $formes[$idTypeEntite];
    }
    return 'diamond';
}

function assignerCouleur($idTypeEntite=''){
    $couleurs = array(
        '1'=> 'box',        // Personne physique
        '2'=> 'ellipse',    // Personne morale
        '3'=> 'ellipse',    // Média
    );
    if( isset($couleurs[$idTypeEntite]) ) {
        return $couleurs[$idTypeEntite];
    }
    return 'diamond';
}

/**
 * Va lire les tableurs de données sur github puis filtre, rage et trie les informations dont on se servira.
 * @return array[] array( array(id1:nom1, id2:nom2,...), array(nom1:id1, nom2:id2,...) array( array(origine=>id1, cible=>id2), array(origine=>id2, cible=>idY) )
 */
function recupererDonnesNettoyees(){
    // D'abord les entités.
    $idEntiteNomEntite  = array();
    $nomEntiteIdEntite  = array();
    $idEntiteTypeEntite = array();
    $entites = file_get_contents('https://raw.githubusercontent.com/mdiplo/Medias_francais/master/medias_francais.tsv');
    $entites = explode("\n", $entites); // Suppression de la première ligne (en-tête du tableur).
    array_shift($entites);
    foreach ($entites as $entite) {
        if($entite==''){
            break;
        }
        $entite = explode("\t", $entite);
        $idEntiteNomEntite[$entite[0]]  = $entite[1];
        $nomEntiteIdEntite[$entite[1]]  = $entite[0];
        $idEntiteTypeEntite[$entite[0]] = $entite[3];
    }

    // Puis les relations.
    $relations=array();
    $liensVrac = file_get_contents('https://raw.githubusercontent.com/mdiplo/Medias_francais/master/relations_medias_francais.tsv');
    $liensVrac = explode("\n", $liensVrac); // Suppression de la première ligne (en-tête du tableur).
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

    return array($idEntiteNomEntite, $nomEntiteIdEntite, $idEntiteTypeEntite, $relations);
}

function recupererLogos($nomEntite=''){
    $logos = array(
        'Claude Perdriel' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Sophia Publications' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/sophiapublications.png',
        'Groupe Perdriel' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupeperdriel.jpg',
        'L’histoire' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lhistoire.png',
        'Historia' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/historia.gif',
        'Challenges' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/challenges.png',
        'Sciences & Avenir' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/sciencesetavenir.png',
        'L’Opinion' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lopinion.jpg',
        'Prisa' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/prisa.png',
        'Xavier Niel' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Matthieu Pigasse' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Les Nouvelles Éditions indépendantes' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lnei.png',
        'Le Nouveau Monde' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Monde libre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Monde SA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Obs' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lobs.jpg',
        'Prier' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/prier.png',
        'M, le magazine du Monde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/mlemagazinedumonde.png',
        'Le Monde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lemonde.png',
        'Télérama' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/telerama.png',
        'Courrier international' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/courrierinternational.png',
        'Le Monde des religions' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lemondedesreligions.png',
        'La Vie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lavie.png',
        'Le Monde diplomatique' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lemondediplomatique.png',
        'Manière de voir' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/manieredevoir.jpg',
        'Huffingtonpost.fr' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/huffingtonpostfr.png',
        'AOL' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/aol.png',
        'Huffington Post' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/huffingtonpostfr.png',
        'Radio Nova' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/radionova.png',
        'Les Inrockuptibles' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lesinrockuptibles.png',
        'Vice.com' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/vice.jpg',
        'Les Amis du Monde diplomatique' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lesamisdumondediplomatique.jpg',
        'Association Günter-Holzmann' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Dassault' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Dassault' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupedassault.png',
        'Groupe Figaro' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupeFigaro.png',
        'Le Figaro' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lefigaro.png',
        'Figaro Magazine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lefigaromagazine.png',
        'Le Particulier' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leparticulier.png',
        'La Lettre de l’Expansion' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lalettredelexpansion.png',
        'Bernard Arnault' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'LVMH' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lvmh.jpg',
        'Le Parisien' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leparisien.png',
        'Le Parisien Week-End' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leparisienweekend.png',
        'Le Parisien économie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leparisieneconomie.png',
        'Aujourd’hui en France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/aujourdhuienfrance.png',
        'Groupe Les Échos' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupelesechos.png',
        'Les Échos' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lesechos.png',
        'Investir' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/investir.png',
        'Investir Magazine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/investirmagazine.jpg',
        'Les Échos Week-End' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lesechosweekend.png',
        'Radio Classique' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/radioclassique.png',
        'Famille Bettencourt' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nicolas Beytout' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Patrick Drahi' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Altice' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/altice.jpg',
        'Altice France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/alticefrance.png',
        'L’Express' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Libération' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'NextRadioTV' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'BFM TV' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'RMC Découverte' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'RMC' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'BFM Business' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Numéro 23' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Arnaud Lagardère' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Qatar Investment Authority' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Lagardère SCA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Lagardère Active' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal du dimanche' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Paris Match' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Gulli' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Europe 1' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'RFM' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Virgin Radio' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Vincent Bolloré' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Financière de l’Odet' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Bolloré' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'CNews' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Vivendi' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Canal +' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'C8' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'CNews' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'CStar' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Iskandar Safa' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Privinvest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Valmonde' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Valeurs actuelles' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Mieux vivre votre argent' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Yves de Chaisemartin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Daniel Křetínský' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Czech Media Invest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Marianne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'François Pinault' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Artémis' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Sebdo-Le Point' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe L’Agefi' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Point de vue' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Point' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Agefi hebdo' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Mohn' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Bertelsmann' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Gruner + Jahr' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'RTL Group' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Prisma Media' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Capital' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Management' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Harvard Business Review' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe M6' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'M6' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'W9' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        '6ter' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'RTL' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'RTL2' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Fun Radio' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Georges Ghosn' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Ghosn Capital' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'VSD' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Martin et Olivier Bouygues' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Bouygues' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe TF1' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'TF1' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'TFX' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'LCI' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'TF1 Séries Films' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'TMC' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'République française' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Länder de République fédérale d’Allemagne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Renault' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Chaîne parlementaire' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Public Sénat' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'France Médias Monde' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'France Télévision' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Radio France' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Arte France' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Monte Carlo Doualiya' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Radio France International' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'France 24' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/france24.png',
        'TV5 Monde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/tv5monde.png',
        'France 2' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/france2.png',
        'France 3' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/france3.png',
        'France 5' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/france5.png',
        'France 4' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/france4.png',
        'France Ô' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/franceo.png',
        'France Inter' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/franceinter.png',
        'France Musique' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/francemusique.png',
        'France Culture' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/franceculture.png',
        'FIP' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/fip.png',
        'France Bleu' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/francebleu.png',
        'France Info' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/franceinfo.jpg',
        'Mouv’' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/mouv.png',
        'Arte' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/arte.png',
        'ARD / ZDF' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/ardzdf.png',
        'Arte Deutschland TV GmbH' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/arteDeutschlandtv.png',
        'Les Augustins de l’Assomption' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/augustinsdelassomption.jpg',
        'Bayard' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Croix' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Pèlerin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Notre temps' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Jean-Paul Baudecroux' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'NRJ Group' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'NRJ 12' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Chérie 25' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'NRJ' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Rire et Chansons' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Chérie FM' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nostalgie' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Hutin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Hurbain' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Crédit Agricole Nord de France' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Association pour le soutien des principes de la démocratie humaniste' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe SIPA - Ouest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Ouest France' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Courrier de l’Ouest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Maine libre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Presse-Océan' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Presse de la Manche' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Publi Hebdos' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Sofiouest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        '20 Minutes' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Rossel' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Rossel La Voix' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Voix du Nord SA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Voix du Nord' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nord éclair' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nord Littoral' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Courrier picard' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’aisne Nouvelle' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Union - L’Ardennais' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Est-éclair - Libération Champagne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Avenir de l’Artois' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Les Echos du Touquet' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal des Flandres' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Messager' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Essor savoyard' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Pays gessien' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Tribune républicaine' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Savoie' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Phare Dunkerquois' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Semaine dans le Boulonnais' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Réveil de Berck' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal de Montreuil' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’indicateur' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Echo de la Lys' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Crédit Mutuel' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe EBRA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Est républicain' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Républicain lorrain' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'DNA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Vosges Matin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Alsace' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Bien public' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal de Saône-et-Loire' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Progrès' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Dauphiné libéré' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Vaucluse Matin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal de la Haute-Marne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Fondation Varenne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Saint-Cricq' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe La Montagne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Montagne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Populaire du centre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal du centre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Berry républicain' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Yonne Républicaine' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Echo Républicain' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Courrier du Loiret' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Eclaireur du Gâtinais' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Régional de Cosne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Echo Charitois' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Pays Roannais' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Liberté' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Journal de Gien' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La République du Centre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe NRCO' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nouvelle République du Centre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Centre Presse' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'TV Tours' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Baylet' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe La Dépêche' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Dépêche du midi' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Petit Bleu' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Nouvelle République des Pyrénées' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Villefranchois' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Gazette du Comminges' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Les Journaux du midi' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Midi libre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Indépendant' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Centre Presse Aveyron' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Lemoine' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Sud Oues' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Sud Ouest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Charente Libre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Dordogne Libre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Haute Saintonge' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Haute Gironde' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Résistant' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Hebdo de Charente-Maritime' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Dépêche du Bassin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal du Médoc' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Pyrénées Presse' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La République des Pyrénées' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Eclair' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Édouard Coudurier' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Télégramme' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Télégramme' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Poher' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Tébéo' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Tébésud' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Mensuel de Rennes' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Sept Jours à Brest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal des entreprises' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Bretagne magazine' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Bernard Tapie' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nethys' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Publifin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Province de Liège' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe La Provence' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nice-Matin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Corse Matin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Provence' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Jean-Louis Louvel' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Société Normande d’Information et de Communication' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Paris-Normandie' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Havre Libre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Progrès de Fécamp' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Liberté Dimanche' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Havre Dimanche' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Normandie Dimanche' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Philippe Hersant' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Éditions Suisses Holding' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Hersant Média' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Filanosa' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Rhône Média' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Côte (Nyon)' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Express (Neuchâtel)' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Impartial (La Chaux-de-Fonds)' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Nouvelliste' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Gazette de Martigny' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal de Sierre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'France Antilles' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'France Guyane' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Abdoul Cadjee' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal de l’Île de la Réunion' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Benjamin et Ariane de Rothschild' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Lampsane Investissement SA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Slate.fr' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Jean-Sébastien Ferjou' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Pierre Guyot' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Gérard Lignac' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Atlantico.fr' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Franck Julien' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Edi Invest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Jean Christophe Tortora' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Laurent Alexandre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Hima Groupe' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Tribune' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'i24 News' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Elle' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Nouveau Magazine littéraire' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Madison Cox' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Alain Weil' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe L’Opinon' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Ken Fisher' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'RMC Story' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
    );
    if( isset($logos[$nomEntite]) ) {
        return $logos[$nomEntite];
    }
    return 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png';
}