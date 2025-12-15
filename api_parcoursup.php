<?php
// api_parcoursup.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permet à votre JS d'appeler ce script

if (!isset($_GET['url'])) {
    echo json_encode(['error' => 'URL manquante']);
    exit;
}

$url = $_GET['url'];

// Validation basique de l'URL
if (strpos($url, 'parcoursup.fr') === false) {
    echo json_encode(['error' => 'URL invalide. Doit venir de parcoursup.fr']);
    exit;
}

function extraireDonneesParcoursup($url) {
    // 1. Récupération du HTML avec un User-Agent pour ne pas être bloqué
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);

    if ($html === false) {
        return ['error' => 'Impossible de charger la page'];
    }

    // 2. Parsing du HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Supprimer les warnings
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html); // Forcer UTF-8
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $data = [
        'formation' => '',
        'synthese' => [],
        'details' => []
    ];

    // --- PARTIE 0 : Nom de la formation ---
    $formationNode = $xpath->query("//h2[contains(@class, 'fr-h3') and contains(@class, 'fr-my-1w')]");
    if ($formationNode->length > 0) {
        $formationText = trim(preg_replace('/\s+/', ' ', $formationNode->item(0)->textContent));
        // Nettoyer les espaces et les sauts de ligne
        $formationText = preg_replace('/\s*<br\s*\/?>\s*/i', ' - ', $formationText);
        $data['formation'] = trim($formationText);
    }

    // --- PARTIE 1 : La Synthèse (Les gros pourcentages) ---
    // On cherche dans l'onglet ID "div-analyse-cddt-onglet2"
    // Les pourcentages sont souvent dans des balises avec des classes spécifiques ou identifiables par le texte
    
    // On cible les blocs qui contiennent un pourcentage suivi d'un texte
    // Note: La structure exacte peut varier, on cherche le conteneur global
    $containerSynthese = $xpath->query("//div[@id='div-analyse-cddt-onglet2']");

    if ($containerSynthese->length > 0) {
        // On cherche les textes qui ressemblent à "X % Label"
        // Cette approche est générique pour résister aux changements de CSS
        $textContent = $containerSynthese->item(0)->textContent;
        
        // Regex pour capturer "50 % Résultats scolaires"
        preg_match_all('/(\d+)\s*%\s*([a-zA-ZÀ-ÿ\s]+)/u', $textContent, $matches, PREG_SET_ORDER);
        
        // On cible les éléments de type "badge-data" (ignore le 100% hors <li>)
        $badges = $xpath->query(".//li[contains(@class,'badge-data')]", $containerSynthese->item(0));
        $map = [];
        $entries = []; // garder l'ordre d'apparition

        foreach ($badges as $li) {
            // Récupérer proprement le texte du li en normalisant les espaces insécables
            $liText = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $li->textContent)));

            // Essayer d'extraire d'abord via les noeuds explicitement nommés
            $labelNode = $xpath->query(".//div[contains(@class,'badge-data-label')]//div", $li)->item(0);
            $valueNode = $xpath->query(".//div[contains(@class,'badge-data-value')]", $li)->item(0);

            $label = '';
            $pct = null;

            if ($labelNode) {
                $label = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $labelNode->textContent)));
            }

            if ($valueNode) {
                $valueText = trim(str_replace("\xc2\xa0", ' ', $valueNode->textContent));
                if (preg_match('/(\d+(?:[.,]\d+)?)\s*%/u', $valueText, $m)) {
                    $pct = str_replace(',', '.', $m[1]);
                } else {
                    // retirer tout sauf chiffres
                    $num = preg_replace('/[^0-9.,]/u', '', $valueText);
                    $pct = $num !== '' ? str_replace(',', '.', $num) : null;
                }
            }
 
            // Si on n'a pas trouvé via les noeuds, tenter d'extraire depuis le texte complet du li
            if ($label === '' || $pct === null) {
                // Chercher un pourcentage dans le texte complet
                if (preg_match('/(\d+(?:[.,]\d+)?)\s*%/u', $liText, $m2)) {
                    $pct = str_replace(',', '.', $m2[1]);
                }

                // Pour le label, enlever le pourcentage et prendre le reste
                $labelGuess = preg_replace('/\d+(?:[.,]\d+)?\s*%/u', '', $liText);
                // Supprimer mots trop courts ou signes éventuels
                $labelGuess = trim(preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $labelGuess));
                // Parfois le label est sur plusieurs lignes, prendre la partie contenant des lettres
                if ($label === '') {
                    // Si labelGuess contient plusieurs mots, on peut heuristiquement prendre la partie la plus significative
                    $lines = preg_split('/\\n|\r/', $labelGuess);
                    foreach ($lines as $ln) {
                        $ln = trim($ln);
                        if ($ln !== '' && !preg_match('/^\d+$/', $ln)) {
                            $label = $ln;
                            break;
                        }
                    }
                }
            }

            if ($label !== '') {
                // Normaliser l'étiquette (retirer espaces doublons)
                $label = trim(preg_replace('/\s+/u', ' ', $label));
            }

            // Normaliser le pourcentage en entier si possible
            if ($pct !== null && $pct !== '') {
                $pct = (string) (int) round(floatval($pct));
            } else {
                $pct = null;
            }

            // Stocker l'entrée en conservant l'ordre
            $entries[] = ['label' => $label, 'pct' => $pct, 'raw' => $liText];
            if ($label !== '') {
                $map[$label] = $pct;
            }
        }

        // Si certaines étiquettes attendues ne sont pas dans le map, tenter d'assigner
        // par position: on suppose que l'ordre des badges correspond à l'ordre attendu.
        // Ordre et labels forcés
        $expected = ['Résultats scolaires', 'Méthodes de travail', 'Motivation'];
        foreach ($expected as $index => $lbl) {
            if (array_key_exists($lbl, $map)) {
                $data['synthese'][] = ['pourcentage' => $map[$lbl], 'label' => $lbl];
            } else {
                // fallback par position si disponible
                if (isset($entries[$index]) && $entries[$index]['pct'] !== null) {
                    $data['synthese'][] = ['pourcentage' => $entries[$index]['pct'], 'label' => $lbl];
                } else {
                    $data['synthese'][] = ['pourcentage' => null, 'label' => $lbl];
                }
            }
        }
    }

    
    // --- PARTIE 2 : Les Détails (titre-detail-cgev) ---
    // On cherche les sections de détails : chaque div.fr-mb-5w contient un h6 et un ul avec les critères
    $detailSections = $xpath->query("//div[contains(@class, 'fr-mb-5w')]");

    foreach ($detailSections as $sectionNode) {
        $section = [];
        
        // Le titre est dans le h6
        $titleNode = $xpath->query(".//h6", $sectionNode)->item(0);
        if ($titleNode) {
            $section['titre'] = trim(preg_replace('/\s+/', ' ', $titleNode->textContent));
        } else {
            continue; // Pas de titre, skip
        }
        
        // Les critères sont dans les li de la ul.fr-toggle__list
        $criteriaLis = $xpath->query(".//ul[contains(@class, 'fr-toggle__list')]//li", $sectionNode);
        
        $criteres = [];
        foreach ($criteriaLis as $li) {
            $critere = [];
            
            // Titre du critère : dans psup-criteria-detail-title
            $titleCritNode = $xpath->query(".//div[contains(@class, 'psup-criteria-detail-title')]", $li)->item(0);
            if ($titleCritNode) {
                $critere['critere'] = trim(preg_replace('/\s+/', ' ', $titleCritNode->textContent));
            } else {
                $critere['critere'] = "Non spécifié";
            }
            
            // Éléments évalués : dans psup-criteria-detail-text après "Éléments évalués :"
            $textNode = $xpath->query(".//div[contains(@class, 'psup-criteria-detail-text')]", $li)->item(0);
            if ($textNode) {
                $textContent = trim($textNode->textContent);
                // Nettoyer les espaces et newlines
                $textContent = preg_replace('/\s+/', ' ', $textContent);
                // Supprimer le "Éléments évalués :" du début
                $textContent = preg_replace('/^Éléments évalués\s*:\s*/i', '', $textContent);
                $critere['elements_evalues'] = trim($textContent);
            } else {
                $critere['elements_evalues'] = "Non spécifiés";
            }
            
            // Importance : dans psup-rating-text
            $ratingNode = $xpath->query(".//span[contains(@class, 'psup-rating-text')]", $li)->item(0);
            if ($ratingNode) {
                $critere['importance'] = trim($ratingNode->textContent);
            } else {
                $critere['importance'] = "Non spécifiée";
            }
            
            $criteres[] = $critere;
        }
        
        $section['criteres'] = $criteres;
        $data['details'][] = $section;
    }

    return $data;
}

echo json_encode(extraireDonneesParcoursup($url), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>