<?php
/**
 * Project : qui_possede_les_medias
 * File : graph.php
 * @author  Pierre-Alexandre RACINE <patcha.dev at{@} gmail dot[.] com>
 * @copyright Copyright (c) 2019, Pierre-Alexandre RACINE <patcha.dev at{@} gmail dot[.] com>
 * @license http://www.gnu.org/licenses/lgpl.html
 * @date 21/04/2020
 * @link
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


function recupererCodeGraphe($options=array()){
    $donnees = recupererDonnesNettoyees();
    $logos = recupererLogos();
    return afficherGraphe($donnees[0], $donnees[1], $donnees[2], $donnees[3], $options);
}



function afficherGraphe(
    array &$tableauIdNom = array(),
    array &$tableauNomId = array(),
    array &$typeEntites  = array(),
    array &$relations    = array(),
    array &$options      = array()
){
    if($options['page_web_complete']) {
        return genererEnTete() . genererBloc($options) . genererJavascript($tableauIdNom, $tableauNomId, $typeEntites, $relations, $options) . genererPiedDePage();
    }
    return genererBloc($options) . genererJavascript($tableauIdNom, $tableauNomId, $typeEntites, $relations, $options);
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
    array &$relations    = array(),
    array &$options      = array()
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
    $idBloc='mynetwork';
    if( isset($options['CSS']['id_graphe']) ) {
        $idBloc = $options['CSS']['id_graphe'];
    }
    $codeJS .= '
        // create a network
        var container = document.getElementById(\''  . $idBloc . '\');
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

function genererBloc($options=array()){
    isset($options['CSS']['id_graphe']) ? $id = $options['CSS']['id_graphe'] : $id = '';
    isset($options['CSS']['classes_graphe']) ? $classes = $options['CSS']['classes_graphe'] : $id = '';
    ( isset($options['dimensions']['largeur_graphe']) && isset($options['dimensions']['hauteur_graphe'])) ? $style=' width: ' . $options['dimensions']['largeur_graphe'] . '; height: ' . $options['dimensions']['hauteur_graphe'] . ';' : $style=' witdh:1000px;height=800px;';
    return '<div id="' . $options['CSS']['id_graphe'] . '" class="' . $classes . '" style="' . $style . '"></div>';
}

function genererEnTete(){
    return '<!doctype html>
<html>
<head>
  <title>Qui possède les médias français ?</title>
    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
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
        'Sophia Publications' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/sophiapublications.webp',
        'Groupe Perdriel' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupeperdriel.webp',
        'L’histoire' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lhistoire.webp',
        'Historia' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/historia.gif',
        'Challenges' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/challenges.webp',
        'Sciences & Avenir' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/sciencesetavenir.webp',
        'L’Opinion' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lopinion.webp',
        'Prisa' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/prisa.webp',
        'Xavier Niel' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Matthieu Pigasse' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Les Nouvelles Éditions indépendantes' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lnei.webp',
        'Le Nouveau Monde' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Monde libre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Monde SA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Obs' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lobs.webp',
        'Prier' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/prier.webp',
        'M, le magazine du Monde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/mlemagazinedumonde.webp',
        'Le Monde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lemonde.webp',
        'Télérama' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/telerama.webp',
        'Courrier international' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/courrierinternational.webp',
        'Le Monde des religions' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lemondedesreligions.webp',
        'La Vie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lavie.webp',
        'Le Monde diplomatique' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lemondediplomatique.webp',
        'Manière de voir' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/manieredevoir.webp',
        'Huffingtonpost.fr' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/huffingtonpostfr.webp',
        'AOL' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/aol.webp',
        'Huffington Post' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/huffingtonpostfr.webp',
        'Radio Nova' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/radionova.webp',
        'Les Inrockuptibles' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lesinrockuptibles.webp',
        'Vice.com' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/vice.webp',
        'Les Amis du Monde diplomatique' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lesamisdumondediplomatique.webp',
        'Association Günter-Holzmann' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Dassault' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Dassault' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupedassault.webp',
        'Groupe Figaro' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupeFigaro.webp',
        'Le Figaro' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lefigaro.webp',
        'Figaro Magazine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lefigaromagazine.webp',
        'Le Particulier' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leparticulier.webp',
        'La Lettre de l’Expansion' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lalettredelexpansion.webp',
        'Bernard Arnault' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'LVMH' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lvmh.webp',
        'Le Parisien' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leparisien.webp',
        'Le Parisien Week-End' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leparisienweekend.webp',
        'Le Parisien économie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leparisieneconomie.webp',
        'Aujourd’hui en France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/aujourdhuienfrance.webp',
        'Groupe Les Échos' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupelesechos.webp',
        'Les Échos' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lesechos.webp',
        'Investir' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/investir.webp',
        'Investir Magazine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/investirmagazine.webp',
        'Les Échos Week-End' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lesechosweekend.webp',
        'Radio Classique' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/radioclassique.webp',
        'Famille Bettencourt' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nicolas Beytout' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Patrick Drahi' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Altice' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/altice.webp',
        'Altice France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/alticefrance.webp',
        'L’Express' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lexpress.webp',
        'Libération' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/liberation.webp',
        'NextRadioTV' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/nextradiotv.webp',
        'BFM TV' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/bfmtv.webp',
        'RMC Découverte' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/rmcdecouverte.webp',
        'RMC' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/rmc.webp',
        'BFM Business' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/bfmbusiness.webp',
        'Numéro 23' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/numero23.webp',
        'Arnaud Lagardère' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Qatar Investment Authority' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/qatarinvestmentauthority.webp',
        'Lagardère SCA' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lagarderesca.webp',
        'Lagardère Active' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lagardereactive.webp',
        'Le Journal du dimanche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldudimanche.webp',
        'Paris Match' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/parismatch.webp',
        'Gulli' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/gulli.webp',
        'Europe 1' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/europe1.svg',
        'RFM' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/rfm.webp',
        'Virgin Radio' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/virginradio.webp',
        'Vincent Bolloré' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Financière de l’Odet' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/financieredelodet.webp',
        'Bolloré' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'CNews' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/cnews.svg',
        'Vivendi' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/vivendi.svg',
        'Canal +' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/canal+.svg',
        'C8' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/c8.svg',
        'CNews' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'CStar' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/cstar.webp',
        'Iskandar Safa' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Privinvest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/privinvest.webp',
        'Groupe Valmonde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/valmonde.webp',
        'Valeurs actuelles' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/valeursactuelles.webp',
        'Mieux vivre votre argent' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/mieuxvivrevotreargent.webp',
        'Yves de Chaisemartin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Daniel Křetínský' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Czech Media Invest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/czechmediainvest.svg',
        'Marianne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/marianne.webp',
        'François Pinault' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Artémis' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/artemis.svg',
        'Groupe Sebdo-Le Point' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe L’Agefi' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/agefi.webp',
        'Point de vue' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/pointdevue.webp',
        'Le Point' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lepoint.svg',
        'L’Agefi hebdo' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lagefihebdo.webp',
        'Famille Mohn' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Bertelsmann' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/bertelsmann.webp',
        'Gruner + Jahr' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/grunerjahr.svg',
        'RTL Group' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/rtlgroup.svg',
        'Prisma Media' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/prismamedia.webp',
        'Capital' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/capital.svg',
        'Management' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/management.webp',
        'Harvard Business Review' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/harvardbusinessreview.webp',
        'Groupe M6' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupem6.webp',
        'M6' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/m6.svg',
        'W9' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/w9.webp',
        '6ter' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/6ter.webp',
        'RTL' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/rtl.svg',
        'RTL2' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/rtl2.webp',
        'Fun Radio' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/funradio.webp',
        'Georges Ghosn' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Ghosn Capital' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'VSD' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/vsd.webp',
        'Martin et Olivier Bouygues' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Bouygues' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/bouygues.webp',
        'Groupe TF1' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupetf1.webp',
        'TF1' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/tf1.svg',
        'TFX' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/tfx.svg',
        'LCI' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lci.webp',
        'TF1 Séries Films' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/tf1seriesfilms.svg',
        'TMC' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/tmc.svg',
        'République française' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/republiquefrancaise.svg',
        'Länder de République fédérale d’Allemagne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/bundesrepublikdeutschland.svg',
        'Renault' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/renault.webp',
        'La Chaîne parlementaire' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lachaineparlementaire.svg',
        'Public Sénat' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/publicsenat.svg',
        'France Médias Monde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/francemediasmonde.webp',
        'France Télévision' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/francetelevisions.webp',
        'Radio France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/radiofrance.svg',
        'Arte France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/artefrance.webp',
        'Monte Carlo Doualiya' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/montecarlodoualiya.svg',
        'Radio France International' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/radiofranceinternational.svg',
        'France 24' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/france24.webp',
        'TV5 Monde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/tv5monde.webp',
        'France 2' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/france2.webp',
        'France 3' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/france3.webp',
        'France 5' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/france5.webp',
        'France 4' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/france4.webp',
        'France Ô' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/franceo.webp',
        'France Inter' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/franceinter.webp',
        'France Musique' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/francemusique.webp',
        'France Culture' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/franceculture.webp',
        'FIP' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/fip.webp',
        'France Bleu' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/francebleu.webp',
        'France Info' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/franceinfo.webp',
        'Mouv’' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/mouv.webp',
        'Arte' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/arte.webp',
        'ARD / ZDF' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/ardzdf.webp',
        'Arte Deutschland TV GmbH' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/arteDeutschlandtv.webp',
        'Les Augustins de l’Assomption' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/augustinsdelassomption.webp',
        'Bayard' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/bayard.webp',
        'La Croix' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lacroix.svg',
        'Pèlerin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/pelerin.webp',
        'Notre temps' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/npytretemps.webp',
        'Jean-Paul Baudecroux' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'NRJ Group' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/nrjgroup.webp',
        'NRJ 12' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/nrj12.svg',
        'Chérie 25' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/cherie25.svg',
        'NRJ' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/nrj.webp',
        'Rire et Chansons' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/rireetchansons.webp',
        'Chérie FM' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/cheriefm.webp',
        'Nostalgie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/nostalgie.webp',
        'Famille Hutin' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Famille Hurbain' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Crédit Agricole Nord de France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/creditagricolenorddefrance.webp',
        'Association pour le soutien des principes de la démocratie humaniste' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe SIPA - Ouest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Ouest France' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/ouestfrance.svg',
        'Le Courrier de l’Ouest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lecourrierdelouest.svg',
        'Le Maine libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lemainelibre.svg',
        'Presse-Océan' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/presseocean.svg',
        'La Presse de la Manche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lapressedelamanche.webp',
        'Publi Hebdos' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/publihebdos.webp',
        'Sofiouest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/sofiouest.webp',
        '20 Minutes' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/20minutes.svg',
        'Groupe Rossel' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/grouperossel.svg',
        'Groupe Rossel La Voix' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/grouperossellavoix.webp',
        'La Voix du Nord SA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Voix du Nord' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lavoixdunord.webp',
        'Nord éclair' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/nordeclair.svg',
        'Nord Littoral' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/nordlittoral.webp',
        'Courrier picard' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/courrierpicard.webp',
        'L’aisne Nouvelle' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/laisnenouvelle.webp',
        'L’Union - L’Ardennais' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lunionlardennais.webp',
        'L’Est-éclair - Libération Champagne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lesteclair.webp',
        'L’Avenir de l’Artois' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lavenirdelartois.webp',
        'Les Echos du Touquet' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lesechosdutouquet.webp',
        'Le Journal des Flandres' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldesflandres.webp',
        'Le Messager' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'L’Essor savoyard' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lessorsavoyard.webp',
        'Le Pays gessien' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lepaysgessien.webp',
        'La Tribune républicaine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/latribunerepublicaine.webp',
        'La Savoie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lasavoie.webp',
        'Le Phare Dunkerquois' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lepharedunkerquois.webp',
        'La Semaine dans le Boulonnais' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lasemainedansleboulonnais.webp',
        'Le Réveil de Berck' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lereveildeberck.webp',
        'Le Journal de Montreuil' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldemontreuil.webp',
        'L’indicateur' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lindicateur.webp',
        'L’Echo de la Lys' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lechodelalys.webp',
        'Crédit Mutuel' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/creditmutuel.webp',
        'Groupe EBRA' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupeebra.webp',
        'L’Est républicain' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lestrepublicain.webp',
        'Le Républicain lorrain' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lerepublicainlorrain.webp',
        'DNA' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/dernieresnouvellesdalsace.svg',
        'Vosges Matin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/vosgesmatin.webp',
        'L’Alsace' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lalsace.webp',
        'Le Bien public' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lebienpublic.webp',
        'Le Journal de Saône-et-Loire' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldesaoneetloire.svg',
        'Le Progrès' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leprogres.webp',
        'Le Dauphiné libéré' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/ledauphinelibere.webp',
        'Vaucluse Matin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/vauclusematin.webp',
        'Le Journal de la Haute-Marne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldelahautemarne.webp',
        'Fondation Varenne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/fondationvarenne.webp',
        'Famille Saint-Cricq' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe La Montagne' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Montagne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lamontagne.webp',
        'Le Populaire du centre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lepopulaireducentre.webp',
        'Le Journal du centre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournalducentre.webp',
        'Le Berry républicain' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leberryrepublicain.svg',
        'L’Yonne Républicaine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lyonnerepublicaine.webp',
        'L’Echo Républicain' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lechorepublicain.webp',
        'Courrier du Loiret' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lecourrierduloiret.webp',
        'L’Eclaireur du Gâtinais' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leclaireurdugatinais.svg',
        'Régional de Cosne' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leregionaldecosnes.webp',
        'L’Echo Charitois' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lechocharitois.webp',
        'Pays Roannais' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lepaysroannais.webp',
        'La Liberté' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/laliberte.svg',
        'Journal de Gien' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldegien.webp',
        'La République du Centre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/larepubliqueducentre.webp',
        'Groupe NRCO' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupenrco.webp',
        'Nouvelle République du Centre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lanouvellerepubliqueducentre.webp',
        'Centre Presse' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/centrepresse.webp',
        'TV Tours' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/tvtours.webp',
        'Famille Baylet' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe La Dépêche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupeladepeche.webp',
        'La Dépêche du midi' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/ladepechedumidi.webp',
        'Le Petit Bleu' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lepopulaireducentre.webp',
        'La Nouvelle République des Pyrénées' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lanouvellerepubliquedespyrenees.webp',
        'Le Villefranchois' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/levillefranchois.webp',
        'La Gazette du Comminges' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lagazetteducomminges.webp',
        'Les Journaux du midi' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Midi libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/midilibre.webp',
        'L’Indépendant' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lindependant.webp',
        'Centre Presse Aveyron' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/centrepreseaveyron.webp',
        'Famille Lemoine' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Sud Ouest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupesudouest.webp',
        'Sud Ouest' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/sudouest.webp',
        'Charente Libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/charentelibre.webp',
        'Dordogne Libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/dordognelibre.webp',
        'Haute Saintonge' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/hautesaintonge.webp',
        'Haute Gironde' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/hautegironde.webp',
        'Le Résistant' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leresistant.webp',
        'L’Hebdo de Charente-Maritime' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lhebdodecharentemaritime.webp',
        'La Dépêche du Bassin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/ladepechedubassin.webp',
        'Le Journal du Médoc' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldumedoc.webp',
        'Pyrénées Presse' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La République des Pyrénées' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/larepubliquedespyrenees.webp',
        'L’Eclair' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leclair.webp',
        'Édouard Coudurier' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Télégramme' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupetelegramme.webp',
        'Le Télégramme' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/letelegramme.webp',
        'Le Poher' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lepoher.webp',
        'Tébéo' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/tebeo.svg',
        'Tébésud' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/tebesud.webp',
        'Le Mensuel de Rennes' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lemensuelderennes.webp',
        'Sept Jours à Brest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal des entreprises' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldesentreprises.webp',
        'Bretagne magazine' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/bretagnemagazine.webp',
        'Bernard Tapie' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Nethys' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/nethys.webp',
        'Publifin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/publifin.webp',
        'Province de Liège' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/provincedeliege.webp',
        'Groupe La Provence' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupelaprovence.webp',
        'Nice-Matin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/nicematin.webp',
        'Corse Matin' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/corsematin.webp',
        'La Provence' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/laprovence.svg',
        'Jean-Louis Louvel' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Société Normande d’Information et de Communication' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/snic.webp',
        'Paris-Normandie' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/parisnormandie.webp',
        'Havre Libre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lehavrelibre.webp',
        'Le Progrès de Fécamp' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/leprogresdefecamp.webp',
        'Liberté Dimanche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/libertedimanche.webp',
        'Havre Dimanche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/havredimanche.webp',
        'Normandie Dimanche' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/normandiedimanche.webp',
        'Philippe Hersant' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Éditions Suisses Holding' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe Hersant Média' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/groupehersantmedia.webp',
        'Groupe Filanosa' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Rhône Média' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Côte (Nyon)' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lacote.webp',
        'L’Express (Neuchâtel)' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lexpress.webp',
        'L’Impartial (La Chaux-de-Fonds)' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/limpartial.webp',
        'Le Nouvelliste' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lenouvelliste.webp',
        'La Gazette de Martigny' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lagazettedemartigny.webp',
        'Le Journal de Sierre' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldesierre.gif',
        'France Antilles' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/franceantilles.webp',
        'France Guyane' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/franceguyane.webp',
        'Abdoul Cadjee' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Le Journal de l’Île de la Réunion' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lejournaldeliledelareunion.jp',
        'Benjamin et Ariane de Rothschild' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Lampsane Investissement SA' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Slate.fr' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/slate.webp',
        'Jean-Sébastien Ferjou' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Pierre Guyot' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Gérard Lignac' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Atlantico.fr' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/atlantico.webp',
        'Franck Julien' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Edi Invest' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Jean Christophe Tortora' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Laurent Alexandre' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Hima Groupe' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'La Tribune' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/latribune.webp',
        'i24 News' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/i24news.webp',
        'Elle' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/elle.webp',
        'Le Nouveau Magazine littéraire' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/lenouveaumagazinelitteraire.webp',
        'Madison Cox' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Alain Weil' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Groupe L’Opinon' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'Ken Fisher' => 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png',
        'RMC Story' => 'https://p-a-racine.fr/lib/articles/qui_possede_les_medias_francais/logos/rmcstory.svg',
    );
    if( isset($logos[$nomEntite]) ) {
        return $logos[$nomEntite];
    }
    return 'https://p-a-racine.fr/vue/images/icons8-accueil-24.png';
}