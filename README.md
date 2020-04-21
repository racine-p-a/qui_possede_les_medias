# qui_possede_les_medias

## Version consultable du graphe

Vous pouvez consulter une version du graphe sur mon
[site personnel](https://p-a-racine.fr/index.php?article=medias). 

## Utilisation

### Installation dans vos projets.

Vous pouvez importer le graphe dans n'importe laquelle de vos pages.
Pensez juste à inclure vis.js come bibliothèque javascript.

```html
<html>
<head>
    ...
    <!-- C'est cette bibliothèque qui gère les graphes. -->
    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    ...
</head>
<body>
```

Une fois que c'est fait, vous pouvez importe le code et générer le code
du graphe.

```php
// Import du code
require_once './graph.php';

$mes_options = array(
    'CSS' => array(
        'id_graphe'=>'blocGrapheMedia',
        'classes_graphe'    => 'classe1 classe2 clase3'
    ),
    'dimensions' => array(
        'largeur_graphe' => '1000px',
        'hauteur_graphe' => '800px',
    ),
    'page_web_complete' => false // Notez que si vous mettez à true,
);                               //  vous obtiendrez une page minimale HTML complète

// Ceci contient le code HTML d'un <div> contenant l'intégralité du graphe.
$mon_code_du_graphe = recupererCodeGraphe($mes_options);
```

## TODO

- éventuel système de filtres/sélection ?