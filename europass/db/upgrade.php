<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2012 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage artefact-europass
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2009-2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

function xmldb_artefact_europass_upgrade($oldversion=0) {
    
    $status = true;

    if ($oldversion < 2008012200) {
        if (is_mysql()) {
            $inttype = 'BIGINT(10)';
        }
        else {
            $inttype = 'INTEGER';
        }
        foreach (array(
            'artefact_europass_mothertongue',
			'artefact_europass_otherlanguage') as $table) {
            $records = get_records_array($table, '', '', 'experiencestartdate DESC', 'id,experiencestartdate,experienceenddate,certificatedate');
            // Sigh. table_column is screwed beyond belief. We let it do its 
            // work (in the case of start and stopdate at least because it does 
            // cast the columns OK), then fix its bugs
            execute_sql('ALTER TABLE {' . $table . '} ADD displayorder ' . $inttype);
            table_column($table, 'experiencestartdate', 'experiencestartdate', 'text', null, null, '', 'not null');
            table_column($table, 'experienceenddate', 'experienceenddate', 'text', null, null, '', '');
            table_column($table, 'certificatedate', 'certificatedate', 'text', null, null, '', '');

            // MySQL docs say:
            //  * BLOB and TEXT columns cannot have DEFAULT values.
            // It turns out they do - a default of ''. And dropping this results in:
            // mysql> ALTER TABLE "artefact_resume_employmenthistory" ALTER COLUMN startdate DROP DEFAULT;
            // ERROR 1101 (42000): BLOB/TEXT column 'startdate' can't have a default value
            //
            if (is_postgres()) {
                execute_sql('ALTER TABLE {' . $table . '} ALTER COLUMN experiencestartdate DROP DEFAULT');
                execute_sql('ALTER TABLE {' . $table . '} ALTER COLUMN experienceenddate DROP DEFAULT');
                execute_sql('ALTER TABLE {' . $table . '} ALTER COLUMN certificatedate DROP DEFAULT');
            }

            if (!empty($records)) {
                foreach ($records as $k => $r) {
                    set_field($table, 'displayorder', $k, 'id', $r->id);
                    set_field($table, 'experiencestartdate', 
                              format_date(strtotime($r->startdate), 'strftimedate', 'current', 'artefact.europass'),
                              'id', $r->id);
                    set_field($table, 'experienceenddate', 
                              format_date(strtotime($r->enddate), 'strftimedate', 'current', 'artefact.europass'),
                              'id', $r->id);
                    set_field($table, 'certificatedate', 
                              format_date(strtotime($r->enddate), 'strftimedate', 'current', 'artefact.europass'),
                              'id', $r->id);
                }
            }
            if (is_mysql()) {
                execute_sql('ALTER TABLE {' . $table .'} MODIFY displayorder ' . $inttype . ' NOT NULL');
                execute_sql('ALTER TABLE {' . $table .'} MODIFY experiencestartdate TEXT NOT NULL');
            }
            else {
                execute_sql('ALTER TABLE {' . $table . '} ALTER displayorder SET NOT NULL');
                execute_sql('ALTER TABLE {' . $table . '} ALTER COLUMN experiencestartdate SET NOT NULL');
            }
        }
    }

    if ($oldversion < 2012030200) {
		// Create 'artefact_europass_otherlanguage_diploma' table...
        $table = new XMLDBTable('artefact_europass_languagediploma');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addFieldInfo('artefact', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL);
        $table->addFieldInfo('languageid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL);
        $table->addFieldInfo('certificate', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->addFieldInfo('awardingbody', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->addFieldInfo('certificatedate', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->addFieldInfo('europeanlevel', XMLDB_TYPE_CHAR, 2);
        $table->addFieldInfo('displayorder', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('artefactfk', XMLDB_KEY_FOREIGN, array('artefact'), 'artefact', array('id'));
        $table->addKeyInfo('languagefk', XMLDB_KEY_FOREIGN, array('languageid'), 'artefact_europass_otherlanguage', array('id'));
        create_table($table);

		// Create 'artefact_europass_otherlanguage_experience' table...
        $table = new XMLDBTable('artefact_europass_languageexperience');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addFieldInfo('artefact', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL);
        $table->addFieldInfo('languageid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL);
        $table->addFieldInfo('startdate', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->addFieldInfo('enddate', XMLDB_TYPE_TEXT, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->addFieldInfo('displayorder', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('artefactfk', XMLDB_KEY_FOREIGN, array('artefact'), 'artefact', array('id'));
        $table->addKeyInfo('languagefk', XMLDB_KEY_FOREIGN, array('languageid'), 'artefact_europass_otherlanguage', array('id'));
        create_table($table);

		// Write values from 'artefact_europass_otherlanguage' to 'artefact_europass_otherlanguage_diploma'...
		// Write values from 'artefact_europass_otherlanguage' to 'artefact_europass_otherlanguage_experience'...
		$sql = "SELECT * FROM {artefact_europass_otherlanguage} l";
		if ($records = get_records_sql_array($sql, array())) {
			foreach ($records as $r) {
				if (empty($r->certificate) && empty($r->awardingbody) && empty($r->certificatedate) && empty($r->europeanlevel)) {
					// Do nothing...
				} else {
					$diploma = new StdClass;
					$diploma->languageid      = $r->id;
					$diploma->certificate     = $r->certificate;
					$diploma->awardingbody    = $r->awardingbody;
					$diploma->certificatedate = $r->certificatedate;
					$diploma->europeanlevel   = $r->europeanlevel;
					insert_record('artefact_europass_languagediploma', $diploma);
				}
				
				if (empty($r->experiencestartdate) && empty($r->experienceenddate) && empty($r->experiencedescription)) {
					// Do nothing...
				} else {
					$experience = new StdClass;
					$experience->languageid  = $r->id;
					$experience->startdate   = $r->experiencestartdate;
					$experience->enddate     = $r->experienceenddate;
					$experience->description = $r->experiencedescription;
					insert_record('artefact_europass_languageexperience', $experience);
				}
			}
        }
		
		// Remove unused fields from 'artefact_europass_otherlanguage'...
		execute_sql('ALTER TABLE {artefact_europass_otherlanguage} DROP COLUMN certificate');
		execute_sql('ALTER TABLE {artefact_europass_otherlanguage} DROP COLUMN awardingbody');
		execute_sql('ALTER TABLE {artefact_europass_otherlanguage} DROP COLUMN certificatedate');
		execute_sql('ALTER TABLE {artefact_europass_otherlanguage} DROP COLUMN europeanlevel');
		execute_sql('ALTER TABLE {artefact_europass_otherlanguage} DROP COLUMN experiencestartdate');
		execute_sql('ALTER TABLE {artefact_europass_otherlanguage} DROP COLUMN experienceenddate');
		execute_sql('ALTER TABLE {artefact_europass_otherlanguage} DROP COLUMN experiencedescription');
	}
	
    return $status;
}

?>
