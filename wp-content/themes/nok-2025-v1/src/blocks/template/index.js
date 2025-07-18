import { registerBlockType } from '@wordpress/blocks';
import metadata            from './block.json';
import EditComponent       from './edit.js';

// If your block is purely dynamic (rendered via PHP), you leave out `save`:
registerBlockType( metadata.name, {
    edit: EditComponent,
    save: () => null,
} );
