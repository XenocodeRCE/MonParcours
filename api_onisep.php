<?php
// api_onisep.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Paramètres attendus: spec1 et spec2 (clés des spécialités ex: 'arts', 'bio')
// Permettre aussi l'appel en CLI pour les tests: php api_onisep.php spec1=maths spec2=nsi
if (PHP_SAPI === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

$spec1 = isset($_GET['spec1']) ? $_GET['spec1'] : null;
$spec2 = isset($_GET['spec2']) ? $_GET['spec2'] : null;

if (!$spec1 || !$spec2) {
    echo json_encode(['error' => 'Paramètres manquants. Utilisez spec1 et spec2 en GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Domaines (même ordre que l'UI)
$DOMAINES = [
    "Arts & Industries culturelles",
    "Droit & Sciences politiques",
    "Lettres & Langues",
    "Santé",
    "Sciences du vivant",
    "Sciences éco. & Gestion",
    "Sciences humaines & Sociales",
    "Informatique & Numérique",
    "Sciences de l'ingénieur",
    "Sport"
];

// Spécialités + scores (0-6) indexés comme dans l'UI
$SPECIALITES = [
    "arts" => ["label"=>"Arts Plastiques","scores"=>[6,2,6,2,2,2,4,2,2,4]],
    "bio" => ["label"=>"Biologie Écologie","scores"=>[2,2,2,6,6,2,4,4,4,2]],
    "hggsp"=> ["label"=>"HGGSP","scores"=>[4,6,6,2,2,4,6,2,2,2]],
    "hlp"=> ["label"=>"HLP","scores"=>[4,6,6,4,2,2,6,2,2,2]],
    "llce"=> ["label"=>"LLCE","scores"=>[4,6,6,2,2,4,4,2,2,2]],
    "llca"=> ["label"=>"LLCA","scores"=>[6,6,6,2,2,2,4,2,2,2]],
    "maths"=> ["label"=>"Mathématiques","scores"=>[2,4,2,4,4,6,4,6,6,2]],
    "nsi"=> ["label"=>"NSI","scores"=>[4,2,2,4,4,4,2,6,6,2]],
    "pc"=> ["label"=>"Physique-Chimie","scores"=>[2,2,2,6,6,2,2,4,6,4]],
    "si"=> ["label"=>"Sciences de l'Ingénieur","scores"=>[2,2,2,4,4,2,2,6,6,2]],
    "svt"=> ["label"=>"SVT","scores"=>[2,2,2,6,6,2,4,4,4,4]],
    "ses"=> ["label"=>"SES","scores"=>[4,6,6,2,2,6,6,4,2,4]]
];

// Validation des clés
if (!isset($SPECIALITES[$spec1]) || !isset($SPECIALITES[$spec2])) {
    echo json_encode(['error' => 'Spécialités invalides. Vérifiez spec1 et spec2. Clés valides: ' . implode(', ', array_keys($SPECIALITES))], JSON_UNESCAPED_UNICODE);
    exit;
}

// Les deux spécialités doivent être différentes
if ($spec1 === $spec2) {
    echo json_encode(['error' => 'Les spécialités spec1 et spec2 doivent être différentes.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// DOMAINES_INFO (présentation, diplomes, metiers)
$DOMAINES_INFO = [
    "Arts & Industries culturelles" => [
        'presentation' => "Cet horizon peut intéresser particulièrement des élèves souhaitant s'investir dans les domaines du design, de la création et des arts. Les élèves choisissant cet horizon décident d'approfondir la pratique des disciplines artistiques concernées, mais aussi l'étude de leur histoire, de leurs métiers et/ou de leur économie.",
        'diplomes' => "Licences : arts ; arts plastiques ; arts du spectacle ; cinéma et audiovisuel ; histoire de l'art et archéologie ; musicologie… Et licences professionnelles : communication et valorisation de la création artistique ; gestion de projets et structures artistiques et culturels ; métiers de la médiation par des approches artistiques et culturelles ; métiers de la mode ; métiers du design ; métiers du jeu vidéo ; technique du son et de l'image...<br><br>DN MADE<br>Diplômes des écoles supérieures d'art et de design (DNA ; DNSEP art, communication ou design) ; diplômes des écoles écoles d'art appliqué (DSAA) ; diplômes des écoles d’architecture (DEEA puis DE architecte) ; diplômes des écoles du paysage (DE paysagiste) ; diplômes des écoles de musique, de danse, de théâtre, de cirque et des conservatoires nationaux supérieurs (DNSP comédien, musicien, danseur, artiste de cirque) ; diplômes de l’École du Louvre (diplôme de 1er cycle en histoire de l’art) ; diplôme de l’Institut national du patrimoine ; diplômes des écoles supérieures en audiovisuel (FEMIS, École Louis Lumière)<br><br>BUT (à noter : un nombre de places en BUT est priorisé pour les bacheliers technologiques) : BUT information-communication ; métiers du multimédia et de l’internet<br><br>BTS (à noter : un nombre de places en BTS est priorisé pour les bacheliers professionnels) : BTS conception de produits industriels ; métiers de l’audiovisuel ; photographie<br><br>DEUST formation de base aux métiers du théâtre ; DEUST théâtre<br>DUMI musicien intervenant<br>DE professeur de danse ; DE professeur de musique ; DE professeur de théâtre",
        'metiers' => "<ul><li>Animateur / Animatrice du patrimoine</li><li>Animateur / Animatrice 2D et 3D</li><li>Antiquaire</li><li>Architecte</li><li>Architecte d'intérieur</li><li>Assistant réalisateur / Assistante réalisatrice</li><li>Chargé / Chargée de production</li><li>Comédien / Comédienne</li><li>Commissaire priseur / Commissaire priseuse (ventes volontaires)</li><li>Concepteur / Conceptrice de jeux vidéo</li><li>Conservateur / Conservatrice du patrimoine</li><li>Costumier / Costumière</li><li>Danseur / Danseuse</li><li>Décorateur-scénographe / Décoratrice-scénographe</li><li>Designer produit</li><li>Dessinateur / Dessinatrice de BD</li><li>Illustrateur / Illustratrice</li><li>Médiateur culturel / Médiatrice culturelle</li><li>Mixeur / Mixeuse</li><li>Monteur / Monteuse</li><li>Motion designer</li><li>Musicien / Musicienne</li><li>Professeur / Professeure de danse, de musique</li><li>Régisseur / Régisseuse de spectacle</li><li>Régisseur général / Régisseuse générale de cinéma</li><li>Responsable de projet culturel</li><li>Restaurateur / Restauratrice d’art</li><li>Styliste</li><li>Webdesigner</li><li>...</li></ul>"
    ],
    "Droit & Sciences politiques" => [
        'presentation' => "Cet horizon rassemble l'étude de l'organisation et du fonctionnement des sociétés, du point de vue légal, politique et administratif. Il demande des compétences en expression écrite et orale, ainsi que des capacités d'analyse et de réflexion.",
        'diplomes' => "Licences : droit ; science politique ; administration publique… Licences professionnelles : activités juridiques ; métiers du notariat ; métiers des administrations et collectivités territoriales<br><br>Diplôme d'institut d'études politiques (IEP)<br>Classes préparatoires aux grandes écoles<br><br>BUT (à noter : un nombre de places en BUT est priorisé pour les bacheliers technologiques) : BUT carrières juridiques ; BUT gestion des entreprises et des administrations…<br><br>BTS collaborateur juriste notarial (à noter : un nombre de places en BTS est priorisé pour les bacheliers professionnels)<br>...",
        'metiers' => "<ul><li>Administrateur / Administratrice de biens</li><li>Assistant / Assistante en RH (ressources humaines)</li><li>Attaché / Attachée d’administration</li><li>Avocat / Avocate</li><li>Collaborateur / Collaboratrice actes courants</li><li>Collaborateur / Collaboratrice de commissaire de justice</li><li>Collaborateur / Collaboratrice de notaire</li><li>Commissaire de justice</li><li>Commissaire-priseur / Commissaire-priseuse (ventes volontaires)</li><li>Conseiller / Conseillère pénitentiaire d'insertion et de probation</li><li>Diplomate</li><li>Directeur / Directrice des services pénitentiaires</li><li>Éducateur / Éducatrice de la protection judiciaire de la jeunesse</li><li>Greffier / Greffière</li><li>Juriste d'entreprise</li><li>Juge</li><li>Magistrat / Magistrate</li><li>Notaire</li><li>Officier / Officière de police</li><li>Rédacteur territorial / Rédactrice territoriale</li><li>Responsable de ressources humaines</li><li>Secrétaire juridique</li><li>...</li></ul>"
    ],
    "Lettres & Langues" => [
        'presentation' => "Cet horizon rassemble les formations liées à la langue, à la littérature, aux cultures et aux médias. Il inclut aussi les compétences en communication et en analyse critique, indispensables aux métiers de l'écriture, de l'édition et de la traduction.",
        'diplomes' => "Licences : lettres modernes ; LLCER ; sciences du langage ; langues étrangères appliquées (LEA) ; information-communication ; histoire ; édition ; DUT/BUT information-communication ; classes préparatoires littéraires ; écoles de journalisme ; masters : traduction, édition, communication internationale.<br><br>Formations possibles : CAPES/agrégation (pour l'enseignement), écoles d'interprétation et de traduction, masters professionnels en communication, formation aux métiers du livre.",
        'metiers' => "<ul><li>Professeur / Professeure de lettres ou de langues</li><li>Traducteur / Traductrice</li><li>Interprète</li><li>Journaliste</li><li>Éditeur / Éditrice</li><li>Correcteur / Correctrice</li><li>Rédacteur / Rédactrice technique</li><li>Chargé / Chargée de communication</li><li>Médiateur / Médiatrice culturel(le)</li><li>Bibliothécaire</li><li>Lexicographe</li><li>Tourisme culturel / guide-conférencier</li><li>...</li></ul>"
    ],
    "Santé" => [
        'presentation' => "Les formations du domaine Santé préparent aux métiers de la prise en charge, de la prévention et de la recherche biomédicale. Elles demandent des acquis scientifiques, une capacité d'organisation et un sens du relationnel.",
        'diplomes' => "PASS / L.AS ; études en santé (médecine, pharmacie, odontologie, maïeutique) ; IFSI (DE infirmier) ; kinésithérapie (IFMK) ; écoles de sages-femmes ; BTS, DUT/BUT paramédicaux ; licences et masters en biologie, santé publique, nutrition ; licences pro métiers du social et paramédical.",
        'metiers' => "<ul><li>Médecin / Médecin spécialiste</li><li>Pharmacien / Pharmacienne</li><li>Infirmier / Infirmière</li><li>Sage-femme</li><li>Kinésithérapeute</li><li>Manipulateur en électroradiologie</li><li>Technicien de laboratoire / Biologiste médical</li><li>Ergothérapeute</li><li>Psychomotricien / Psychomotricienne</li><li>Cadre de santé / Responsable d'établissement</li><li>Recherche médicale / Chercheur</li><li>Épidémiologiste</li><li>...</li></ul>"
    ],
    "Sciences du vivant" => [
        'presentation' => "Sciences du vivant : étude des organismes, des écosystèmes, de la santé animale et végétale, et des ressources naturelles. Convient aux élèves intéressés par la recherche, l'environnement et les métiers de la biologie appliquée.",
        'diplomes' => "Licences : biologie, écologie ; BUT/BTS agronomie, analyses biologiques ; écoles d'ingénieurs agro/agroalimentaire ; masters recherche et professionnels en biologie, écologie, agroécologie ; écoles vétérinaires (concours).<br><br>Formations : IUT, BTS, écoles d'ingénieurs spécialisées, masters professionnels en environnement et biotechnologies.",
        'metiers' => "<ul><li>Biologiste</li><li>Agronome</li><li>Ingénieur agronome</li><li>Vétérinaire</li><li>Généticien / Généticienne</li><li>Ingénieur en biotechnologies</li><li>Écologue / Chargé d'études environnement</li><li>Technicien de laboratoire</li><li>Conseiller en agriculture durable</li><li>Chargé de mission biodiversité</li><li>...</li></ul>"
    ],
    "Sciences éco. & Gestion" => [
        'presentation' => "Ce domaine couvre l'économie, la gestion, le management, la comptabilité, la finance et le marketing. Il s'adresse aux élèves qui aiment les chiffres, l'organisation et la stratégie d'entreprise.",
        'diplomes' => "Licences : économie, gestion, AES ; DUT/BUT gestion des entreprises et des administrations ; BTS : comptabilité, gestion, commerce ; DCG/DSCG (comptabilité) ; écoles de commerce ; masters en finance, management, marketing ; IEP pour les carrières publiques.",
        'metiers' => "<ul><li>Comptable / Expert-comptable (après DCG/DSCG)</li><li>Contrôleur / Contrôleuse de gestion</li><li>Analyste financier / Analyste de crédit</li><li>Chef de produit / Responsable marketing</li><li>Acheteur / Acheteuse</li><li>Responsable ressources humaines</li><li>Auditeur</li><li>Conseiller / Conseillère en gestion</li><li>Chef d'entreprise / Entrepreneur</li><li>...</li></ul>"
    ],
    "Sciences humaines & Sociales" => [
        'presentation' => "Sciences humaines et sociales : sociologie, histoire, philosophie, géographie, psychologie, anthropologie et sciences de l'éducation. Convient aux profils curieux des mécanismes sociaux et du passé.",
        'diplomes' => "Licences : histoire, philosophie, géographie, sociologie, psychologie ; masters en recherche ou professionnels ; IEP ; DUT/BUT information-communication ; licences pro métiers du social ; formations en médiation et intervention sociale.",
        'metiers' => "<ul><li>Sociologue</li><li>Psychologue (formation par la suite)</li><li>Chargé / Chargée d'études sociales</li><li>Conseiller / Conseillère d'insertion</li><li>Médiateur / Médiatrice</li><li>Chargé / Chargée de projet social</li><li>Conservateur du patrimoine</li><li>CPE</li><li>Enseignant / Enseignante</li><li>Journaliste</li><li>...</li></ul>"
    ],
    "Informatique & Numérique" => [
        'presentation' => "Numérique et informatique : conception de systèmes logiciels, développement, réseaux, cybersécurité et data science. Convient à ceux qui aiment résoudre des problèmes et manipuler des technologies.",
        'diplomes' => "Licences d'informatique ; BUT réseaux et télécoms, BUT informatique ; BTS services informatiques aux organisations ; écoles d'ingénieurs en informatique ; masters en data science, cybersécurité, IA ; licences pro métiers du numérique.",
        'metiers' => "<ul><li>Développeur / Développeuse web et mobile</li><li>Ingénieur / Ingénieure logiciel</li><li>Data scientist / Data engineer</li><li>Ingénieur / Ingénieure IA</li><li>Administrateur / Administratrice systèmes et réseaux</li><li>Chef de projet informatique</li><li>Consultant / Consultante en transformation numérique</li><li>Expert / Experte cybersécurité</li><li>Designer UX/UI</li><li>...</li></ul>"
    ],
    "Sciences de l'ingénieur" => [
        'presentation' => "Sciences de l'ingénieur : conception, réalisation et maintenance d'objets et systèmes techniques. Convient aux élèves intéressés par la physique appliquée, les systèmes embarqués et l'innovation technique.",
        'diplomes' => "Prépas, écoles d'ingénieurs (formation généraliste ou spécialisée), BUT/BTS en génie mécanique, génie civil, électrotechnique, chimie ; licences en physique appliquée, matériaux ; masters ingénierie.",
        'metiers' => "<ul><li>Ingénieur / Ingénieure en génie mécanique</li><li>Ingénieur / Ingénieure en génie civil</li><li>Roboticien / Roboticienne</li><li>Automaticien / Automaticienne</li><li>Concepteur / Concepteur produit</li><li>Chef de projet industriel</li><li>Responsable maintenance</li><li>Ingénieur recherche et développement</li><li>...</li></ul>"
    ],
    "Sport" => [
        'presentation' => "Domaine Sport : formation aux métiers de l'entraînement, de l'encadrement, de la performance et des activités physiques adaptées. Il nécessite un bon niveau physique et des compétences pédagogiques.",
        'diplomes' => "Licences STAPS (sciences et techniques des activités physiques et sportives) ; BPJEPS, DEJEPS, DESJEPS (métiers de l'animation et de l'encadrement) ; formations spécialisées (préparation physique, management sportif) ; BTS métiers de la forme.",
        'metiers' => "<ul><li>Éducateur / Éducatrice sportif(ve)</li><li>Entraîneur / Entraîneuse</li><li>Professeur d'EPS</li><li>Préparateur physique</li><li>Manager sportif / Directeur sportif</li><li>Chargé / Chargée de développement des activités sportives</li><li>Animateur / Animatrice sportif</li><li>Masseur-kinésithérapeute du sport</li><li>Responsable de structure (club, centre)</li><li>Educateur sportif adapté (handisport)</li><li>...</li></ul>"
    ]
];

// Calcul des pourcentages par domaine
$s1 = $SPECIALITES[$spec1]['scores'];
$s2 = $SPECIALITES[$spec2]['scores'];

$domains = [];
foreach ($DOMAINES as $i => $name) {
    $sum = intval($s1[$i]) + intval($s2[$i]);
    $percent = (int) round(($sum / 12) * 100);
    $domains[$name] = [
        'percent' => $percent,
        'presentation' => $DOMAINES_INFO[$name]['presentation'],
        'diplomes' => $DOMAINES_INFO[$name]['diplomes'],
        'metiers' => $DOMAINES_INFO[$name]['metiers']
    ];
}

// Top trié
$sorted = $domains;
uasort($sorted, function($a, $b){ return $b['percent'] <=> $a['percent']; });

$top3 = array_slice($sorted, 0, 3, true);
$top = array_key_first($top3);

$result = [
    'specs' => [$spec1, $spec2],
    'recommended' => ['name' => $top, 'percent' => $top3[$top]['percent']],
    'top3' => array_map(function($k,$v){ return ['name'=>$k,'percent'=>$v['percent']]; }, array_keys($top3), $top3),
    'domains' => $domains
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

?>
