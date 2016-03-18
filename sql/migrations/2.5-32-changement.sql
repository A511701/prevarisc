set names 'utf8';

DROP TABLE IF EXISTS `changement`;

CREATE TABLE `changement` (
  `ID_CHANGEMENT` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `LIBELLE_CHANGEMENT` varchar(255) DEFAULT NULL,
  `MESSAGE_CHANGEMENT` text,
  PRIMARY KEY (`ID_CHANGEMENT`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



INSERT INTO `changement` VALUES (1,'Changement de statut','<p>Bonjour,</p>\r\n<p>L\'&eacute;tablissement {etablissementNumeroId} {etablissementLibelle} est pass&eacute; au statut {etablissementStatut}.</p>\r\n<p>Bonne journ&eacute;e,</p>\r\n<p>Pr&eacute;varisc.</p>'),(2,'Changement d\'avis','<p>Bonjour,</p>\r\n<p>L\'&eacute;tablissement {etablissementNumeroId} {etablissementLibelle} est maintenant sous avis {etablissementAvis}.</p>\r\n<p>Bonne journ&eacute;e,</p>\r\n<p>Pr&eacute;varisc.</p>'),(3,'Changement de classement','<p>Bonjour,</p>\r\n<p>L\'&eacute;tablissement {etablissementNumeroId} {etablissementLibelle} est maintenant de cat&eacute;gorie {categorieEtablissement}, de type {typePrincipalEtablissement} - {activitePrincipaleEtablissement}.</p>\r\n<p>Bonne journ&eacute;e,</p>\r\n<p>Pr&eacute;varisc.</p>');
