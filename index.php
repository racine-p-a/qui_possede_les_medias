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
        'L’Express' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lexpress.png',
        'Libération' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/liberation.png',
        'NextRadioTV' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/nextradiotv.png',
        'BFM TV' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/bfmtv.png',
        'RMC Découverte' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/rmcdecouverte.png',
        'RMC' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/rmc.jpg',
        'BFM Business' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/bfmbusiness.png',
        'Numéro 23' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/numero23.png',
        'Arnaud Lagardère' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Qatar Investment Authority' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/qatarinvestmentauthority.png',
        'Lagardère SCA' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lagarderesca.jpg',
        'Lagardère Active' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lagardereactive.png',
        'Le Journal du dimanche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldudimanche.png',
        'Paris Match' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/parismatch.png',
        'Gulli' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/gulli.png',
        'Europe 1' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/europe1.svg',
        'RFM' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/rfm.png',
        'Virgin Radio' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/virginradio.png',
        'Vincent Bolloré' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Financière de l’Odet' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/financieredelodet.png',
        'Bolloré' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'CNews' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/cnews.svg',
        'Vivendi' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/vivendi.svg',
        'Canal +' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/canal+.svg',
        'C8' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/c8.svg',
        'CNews' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'CStar' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/cstar.jpg',
        'Iskandar Safa' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Privinvest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/privinvest.jpg',
        'Groupe Valmonde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/valmonde.jpg',
        'Valeurs actuelles' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/valeursactuelles.jpg',
        'Mieux vivre votre argent' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/mieuxvivrevotreargent.jpg',
        'Yves de Chaisemartin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Daniel Křetínský' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Czech Media Invest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/czechmediainvest.svg',
        'Marianne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/marianne.png',
        'François Pinault' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Artémis' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/artemis.svg',
        'Groupe Sebdo-Le Point' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe L’Agefi' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/agefi.png',
        'Point de vue' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/pointdevue.jpg',
        'Le Point' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lepoint.svg',
        'L’Agefi hebdo' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lagefihebdo.png',
        'Famille Mohn' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Bertelsmann' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/bertelsmann.jpg',
        'Gruner + Jahr' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/grunerjahr.svg',
        'RTL Group' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/rtlgroup.svg',
        'Prisma Media' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/prismamedia.jpg',
        'Capital' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/capital.svg',
        'Management' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/management.png',
        'Harvard Business Review' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/harvardbusinessreview.png',
        'Groupe M6' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupem6.jpg',
        'M6' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/m6.svg',
        'W9' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/w9.png',
        '6ter' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/6ter.png',
        'RTL' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/rtl.svg',
        'RTL2' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/rtl2.png',
        'Fun Radio' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/funradio.jpg',
        'Georges Ghosn' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Ghosn Capital' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'VSD' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/vsd.jpg',
        'Martin et Olivier Bouygues' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Bouygues' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/bouygues.png',
        'Groupe TF1' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupetf1.png',
        'TF1' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/tf1.svg',
        'TFX' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/tfx.svg',
        'LCI' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lci.png',
        'TF1 Séries Films' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/tf1seriesfilms.svg',
        'TMC' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/tmc.svg',
        'République française' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/republiquefrancaise.svg',
        'Länder de République fédérale d’Allemagne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/bundesrepublikdeutschland.svg',
        'Renault' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/renault.jpg',
        'La Chaîne parlementaire' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lachaineparlementaire.svg',
        'Public Sénat' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/publicsenat.svg',
        'France Médias Monde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/francemediasmonde.jpg',
        'France Télévision' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/francetelevisions.png',
        'Radio France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/radiofrance.svg',
        'Arte France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/artefrance.png',
        'Monte Carlo Doualiya' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/montecarlodoualiya.svg',
        'Radio France International' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/radiofranceinternational.svg',
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
        'Bayard' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/bayard.png',
        'La Croix' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lacroix.svg',
        'Pèlerin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/pelerin.jpg',
        'Notre temps' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/npytretemps.jpg',
        'Jean-Paul Baudecroux' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'NRJ Group' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/nrjgroup.png',
        'NRJ 12' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/nrj12.svg',
        'Chérie 25' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/cherie25.svg',
        'NRJ' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/nrj.png',
        'Rire et Chansons' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/rireetchansons.png',
        'Chérie FM' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/cheriefm.png',
        'Nostalgie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/nostalgie.webp',
        'Famille Hutin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Hurbain' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Crédit Agricole Nord de France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/creditagricolenorddefrance.png',
        'Association pour le soutien des principes de la démocratie humaniste' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe SIPA - Ouest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Ouest France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/ouestfrance.svg',
        'Le Courrier de l’Ouest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lecourrierdelouest.svg',
        'Le Maine libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lemainelibre.svg',
        'Presse-Océan' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/presseocean.svg',
        'La Presse de la Manche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lapressedelamanche.png',
        'Publi Hebdos' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/publihebdos.jpg',
        'Sofiouest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/sofiouest.jpg',
        '20 Minutes' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/20minutes.svg',
        'Groupe Rossel' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/grouperossel.svg',
        'Groupe Rossel La Voix' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/grouperossellavoix.png',
        'La Voix du Nord SA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Voix du Nord' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lavoixdunord.jpg',
        'Nord éclair' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/nordeclair.svg',
        'Nord Littoral' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/nordlittoral.png',
        'Courrier picard' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/courrierpicard.jpg',
        'L’aisne Nouvelle' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/laisnenouvelle.png',
        'L’Union - L’Ardennais' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lunionlardennais.jpg',
        'L’Est-éclair - Libération Champagne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lesteclair.jpg',
        'L’Avenir de l’Artois' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lavenirdelartois.jpg',
        'Les Echos du Touquet' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lesechosdutouquet.jpg',
        'Le Journal des Flandres' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldesflandres.jpg',
        'Le Messager' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Essor savoyard' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lessorsavoyard.jpg',
        'Le Pays gessien' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lepaysgessien.jpg',
        'La Tribune républicaine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/latribunerepublicaine.jpg',
        'La Savoie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lasavoie.jpg',
        'Le Phare Dunkerquois' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lepharedunkerquois.jpg',
        'La Semaine dans le Boulonnais' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lasemainedansleboulonnais.jpg',
        'Le Réveil de Berck' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lereveildeberck.jpg',
        'Le Journal de Montreuil' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldemontreuil.jpg',
        'L’indicateur' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lindicateur.jpg',
        'L’Echo de la Lys' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lechodelalys.jpg',
        'Crédit Mutuel' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/creditmutuel.jpg',
        'Groupe EBRA' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupeebra.png',
        'L’Est républicain' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lestrepublicain.jpg',
        'Le Républicain lorrain' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lerepublicainlorrain.jpg',
        'DNA' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/dernieresnouvellesdalsace.svg',
        'Vosges Matin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/vosgesmatin.jpg',
        'L’Alsace' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lalsace.png',
        'Le Bien public' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lebienpublic.png',
        'Le Journal de Saône-et-Loire' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldesaoneetloire.svg',
        'Le Progrès' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leprogres.png',
        'Le Dauphiné libéré' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/ledauphinelibere.jpg',
        'Vaucluse Matin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/vauclusematin.jpg',
        'Le Journal de la Haute-Marne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldelahautemarne.jpg',
        'Fondation Varenne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/fondationvarenne.jpg',
        'Famille Saint-Cricq' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe La Montagne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Montagne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lamontagne.png',
        'Le Populaire du centre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lepopulaireducentre.png',
        'Le Journal du centre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournalducentre.png',
        'Le Berry républicain' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leberryrepublicain.svg',
        'L’Yonne Républicaine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lyonnerepublicaine.png',
        'L’Echo Républicain' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lechorepublicain.png',
        'Courrier du Loiret' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lecourrierduloiret.png',
        'L’Eclaireur du Gâtinais' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leclaireurdugatinais.svg',
        'Régional de Cosne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leregionaldecosnes.png',
        'L’Echo Charitois' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lechocharitois.jpg',
        'Pays Roannais' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lepaysroannais.jpg',
        'La Liberté' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/laliberte.svg',
        'Journal de Gien' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldegien.jpg',
        'La République du Centre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/larepubliqueducentre.jpg',
        'Groupe NRCO' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupenrco.png',
        'Nouvelle République du Centre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lanouvellerepubliqueducentre.png',
        'Centre Presse' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/centrepresse.png',
        'TV Tours' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/tvtours.png',
        'Famille Baylet' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe La Dépêche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupeladepeche.png',
        'La Dépêche du midi' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/ladepechedumidi.webp',
        'Le Petit Bleu' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lepopulaireducentre.png',
        'La Nouvelle République des Pyrénées' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lanouvellerepubliquedespyrenees.jpg',
        'Le Villefranchois' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/levillefranchois.png',
        'La Gazette du Comminges' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lagazetteducomminges.jpg',
        'Les Journaux du midi' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Midi libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/midilibre.png',
        'L’Indépendant' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lindependant.jpg',
        'Centre Presse Aveyron' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/centrepreseaveyron.jpg',
        'Famille Lemoine' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Sud Ouest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupesudouest.png',
        'Sud Ouest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/sudouest.jpg',
        'Charente Libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/charentelibre.png',
        'Dordogne Libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/dordognelibre.png',
        'Haute Saintonge' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/hautesaintonge.png',
        'Haute Gironde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/hautegironde.png',
        'Le Résistant' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leresistant.png',
        'L’Hebdo de Charente-Maritime' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lhebdodecharentemaritime.jpg',
        'La Dépêche du Bassin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/ladepechedubassin.jpg',
        'Le Journal du Médoc' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldumedoc.png',
        'Pyrénées Presse' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La République des Pyrénées' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/larepubliquedespyrenees.png',
        'L’Eclair' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leclair.jpg',
        'Édouard Coudurier' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Télégramme' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupetelegramme.jpg',
        'Le Télégramme' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/letelegramme.jpg',
        'Le Poher' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lepoher.jpg',
        'Tébéo' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/tebeo.svg',
        'Tébésud' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/tebesud.png',
        'Le Mensuel de Rennes' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lemensuelderennes.jpg',
        'Sept Jours à Brest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal des entreprises' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldesentreprises.jpg',
        'Bretagne magazine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/bretagnemagazine.png',
        'Bernard Tapie' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nethys' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/nethys.png',
        'Publifin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/publifin.jpg',
        'Province de Liège' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/provincedeliege.png',
        'Groupe La Provence' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupelaprovence.png',
        'Nice-Matin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/nicematin.png',
        'Corse Matin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/corsematin.jpg',
        'La Provence' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/laprovence.svg',
        'Jean-Louis Louvel' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Société Normande d’Information et de Communication' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/snic.jpg',
        'Paris-Normandie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/parisnormandie.png',
        'Havre Libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lehavrelibre.jpg',
        'Le Progrès de Fécamp' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/leprogresdefecamp.jpg',
        'Liberté Dimanche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/libertedimanche.jpg',
        'Havre Dimanche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/havredimanche.jpg',
        'Normandie Dimanche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/normandiedimanche.jpg',
        'Philippe Hersant' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Éditions Suisses Holding' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Hersant Média' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/groupehersantmedia.jpg',
        'Groupe Filanosa' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Rhône Média' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Côte (Nyon)' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lacote.jpg',
        'L’Express (Neuchâtel)' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lexpress.jpg',
        'L’Impartial (La Chaux-de-Fonds)' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/limpartial.png',
        'Le Nouvelliste' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lenouvelliste.jpg',
        'La Gazette de Martigny' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lagazettedemartigny.jpg',
        'Le Journal de Sierre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldesierre.gif',
        'France Antilles' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/franceantilles.png',
        'France Guyane' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/franceguyane.jpg',
        'Abdoul Cadjee' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal de l’Île de la Réunion' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lejournaldeliledelareunion.jp',
        'Benjamin et Ariane de Rothschild' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Lampsane Investissement SA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Slate.fr' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/slate.png',
        'Jean-Sébastien Ferjou' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Pierre Guyot' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Gérard Lignac' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Atlantico.fr' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/atlantico.jpg',
        'Franck Julien' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Edi Invest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Jean Christophe Tortora' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Laurent Alexandre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Hima Groupe' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Tribune' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/latribune.png',
        'i24 News' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/i24news.png',
        'Elle' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/elle.png',
        'Le Nouveau Magazine littéraire' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/lenouveaumagazinelitteraire.jpg',
        'Madison Cox' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Alain Weil' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe L’Opinon' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Ken Fisher' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'RMC Story' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/rmcstory.svg',
    );
    if( isset($logos[$nomEntite]) ) {
        return $logos[$nomEntite];
    }
    return 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png';
}