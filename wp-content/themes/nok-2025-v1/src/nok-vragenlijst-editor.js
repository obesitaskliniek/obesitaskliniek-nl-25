/**
 * Vragenlijst Editor — Gutenberg sidebar panels for building questionnaires
 *
 * Registers three sidebar panels on the `vragenlijst` CPT editor:
 * - Vragen (Questions): drag-reorder, per-question type/options/branch
 * - Resultaten (Results): condition rule builder, result cards
 * - Instellingen (Settings): display/behavior toggles
 *
 * Reads/writes the _vl_config JSON meta field via WordPress data store.
 */
import {registerPlugin} from '@wordpress/plugins';
import VragenlijstEditor from './vragenlijst-editor/VragenlijstEditor';

registerPlugin('nok-vragenlijst-editor', {
    render: VragenlijstEditor,
});
