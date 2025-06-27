=== Compteur de Ventes Kit ===
Contributors: hadrien
Tags: ventes, kits, compteur, airsam
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.1
License: GPLv2 or later

== Description ==
Gère les ventes de différents kits et calcule un débit valorisé.

== Installation ==
1. Téléversez le plugin dans le dossier `/wp-content/plugins/`.
2. Activez-le via le menu « Extensions » de WordPress.
3. Configurez vos kits et ventes dans le menu « Compteur Ventes ».

== Utilisation ==
- [nombre_facades_respectees] : Affiche la phrase + le nombre
  .facades-label : cible le texte
  .facades-count : cible le nombre
- [nombre_facades_respectees_nb] : Affiche uniquement le nombre
  .facades-count : cible le nombre

- [debit_valorise] : Affiche la phrase + la valeur dynamique
  .debit-label : cible le texte
  .debit-valorise-container : cible la valeur
  .debit-valorise-loading : état de chargement
  .debit-valorise-value : état valeur affichée
- [debit_valorise_nb] : Affiche uniquement la valeur dynamique
  .debit-valorise-container : cible la valeur
  .debit-valorise-loading : état de chargement
  .debit-valorise-value : état valeur affichée

== Personnalisation CSS ==
Ajoutez ce CSS dans Elementor ou le customizer :
.facades-label { font-weight: bold; }
.facades-count { color: #0073aa; }
.debit-label { font-weight: bold; }
.debit-valorise-loading { color: #aaa; }
.debit-valorise-value { color: #d35400; }

== Changelog ==
= 1.1 =
* Ajout des shortcodes _nb pour affichage minimaliste.
* Première version.
