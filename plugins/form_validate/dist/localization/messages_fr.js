(function( factory ) {
	if ( typeof define === "function" && define.amd ) {
		define( ["jquery", "../jquery.validate"], factory );
	} else {
		factory( jQuery );
	}
}(function( $ ) {

/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: FR (French; français)
 */
$.extend($.validator.messages, {
	required: "Ce champ est obligatoire.",
	remote: "Veuillez corriger ce champ.",
	email: "Veuillez indiquer une adresse électronique valide.",
	url: "Veuillez indiquer une URL valide.",
	date: "Veuillez indiquer une date valide.",
	dateISO: "Veuillez indiquer une date valide (ISO).",
	number: "Veuillez indiquer un numéro valide.",
	digits: "Veuillez n'indiquer que des chiffres.",
	creditcard: "Veuillez indiquer un numéro de carte de crédit valide.",
	equalTo: "Les deux champs doivent correspondre.",
	extension: "Veuillez indiquer une valeur avec une extension valide.",
	maxlength: $.validator.format("Veuillez indiquer au maximum {0} caractères."),
	minlength: $.validator.format("Veuillez indiquer au minimum {0} caractères."),
	rangelength: $.validator.format("Veuillez indiquer une valeur qui contient entre {0} et {1} caractères."),
	range: $.validator.format("Veuillez indiquer une valeur entre {0} et {1}."),
	max: $.validator.format("Veuillez indiquer une valeur inférieure ou égale à {0}."),
	min: $.validator.format("Veuillez indiquer une valeur supérieure ou égale à {0}."),
	maxWords: $.validator.format("Veuillez indiquer au maximum {0} mots."),
	minWords: $.validator.format("Veuillez indiquer au minimum {0} mots."),
	rangeWords: $.validator.format("Veuillez indiquer entre {0} et {1} mots."),
	letterswithbasicpunc: "Veuillez indiquer seulement des lettres et des signes de ponctuation.",
	alphanumeric: "Veuillez indiquer seulement des lettres, nombres, espaces et soulignages.",
	lettersonly: "Veuillez indiquer seulement des lettres.",
	nowhitespace: "Veuillez ne pas inscrire d'espaces blancs.",
	ziprange: "Veuillez indiquer un code postal entre 902xx-xxxx et 905-xx-xxxx.",
	integer: "Veuillez indiquer un nombre non décimal qui est positif ou négatif.",
	vinUS: "Veuillez indiquer un numéro d'identification du véhicule (VIN).",
	dateITA: "Veuillez indiquer une date valide.",
	time: "Veuillez indiquer une heure valide entre 00:00 et 23:59.",
	phoneUS: "Veuillez indiquer un numéro de téléphone valide.",
	phoneUK: "Veuillez indiquer un numéro de téléphone valide.",
	mobileUK: "Veuillez indiquer un numéro de téléphone mobile valide.",
	strippedminlength: $.validator.format("Veuillez indiquer au moins {0} caractères."),
	email2: "Veuillez indiquer une adresse électronique valide.",
	url2: "Veuillez indiquer une adresse URL valide.",
	creditcardtypes: "Veuillez indiquer un numéro de carte de crédit valide.",
	ipv4: "Veuillez indiquer une adresse IP v4 valide.",
	ipv6: "Veuillez indiquer une adresse IP v6 valide.",
	require_from_group: "Veuillez indiquer au moins {0} de ces champs.",
	nifES: "Veuillez indiquer un numéro NIF valide.",
	nieES: "Veuillez indiquer un numéro NIE valide.",
	cifES: "Veuillez indiquer un numéro CIF valide.",
	postalCodeCA: "Veuillez indiquer un code postal valide."
});

}));