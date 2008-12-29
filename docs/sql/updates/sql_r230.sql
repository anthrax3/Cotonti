/* Set default file download permission masks for pages */
UPDATE sed_auth SET auth_rights = auth_rights + 4
WHERE auth_code = 'page' AND auth_groupid != 5 AND auth_groupid != 3 AND auth_groupid != 2
AND NOT auth_rights & 4 = 4;

UPDATE sed_auth SET auth_rights_lock = auth_rights_lock - 4
WHERE auth_code = 'page' AND auth_groupid != 5 AND auth_groupid != 3 AND auth_groupid != 2 AND auth_groupid != 4
AND auth_rights_lock & 4 = 4;