# Gestion Vente Kits

Gère les ventes de différents kits et calcule un débit valorisé.

## Fonctionnalités

- Gestion des ventes de kits via l’admin WordPress
- Configuration des kits (nom, débit, constante)
- Quatre shortcodes pour affichage sur le site
- Débit valorisé mis à jour automatiquement (AJAX)
- Compatible Elementor (personnalisation facile via CSS)

## Utilisation des shortcodes

### 1. Nombre de façades respectées

- `[nombre_facades_respectees]`  
  Affiche la phrase + le nombre  
  - `.facades-label` : cible le texte  
  - `.facades-count` : cible le nombre

- `[nombre_facades_respectees_nb]`  
  Affiche uniquement le nombre  
  - `.facades-count` : cible le nombre

### 2. Débit valorisé

- `[debit_valorise]`  
  Affiche la phrase + la valeur dynamique  
  - `.debit-label` : cible le texte  
  - `.debit-valorise-container` : cible la valeur  
  - `.debit-valorise-loading` : état de chargement  
  - `.debit-valorise-value` : état valeur affichée

- `[debit_valorise_nb]`  
  Affiche uniquement la valeur dynamique  
  - `.debit-valorise-container` : cible la valeur  
  - `.debit-valorise-loading` : état de chargement  
  - `.debit-valorise-value` : état valeur affichée

## Exemple de CSS personnalisable

```css
.facades-label { font-weight: bold; }
.facades-count { color: #0073aa; }
.debit-label { font-weight: bold; }
.debit-valorise-loading { color: #aaa; }
.debit-valorise-value { color: #d35400; }
```

## Installation

1. Téléversez le dossier du plugin dans `/wp-content/plugins/`.
2. Activez-le via le menu « Extensions » de WordPress.
3. Configurez vos kits et ventes dans le menu « Compteur Ventes ».

## Support

Pour toute question, consultez la documentation ou ouvrez une issue sur [GitHub](https://github.com/hadrien-samouillan/compteur-ventes-kits).
