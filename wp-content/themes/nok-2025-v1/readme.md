The basic idea of this theme is:
Pages will consist of posts, of a custom post type called "page parts".
This allows for a flexible page structure, where each page can be composed of various sections or components, making it easier to manage and update content.

Pages will be constructed using the gutenberg block "embed-nok-page-part" which offers (at this stage) a dropdown of page part posts found in the backend.

Page Part Posts will be created using the regular post editor, and will have a dropdown of available styles to choose from. These styles, or "templates" are defined in /template-parts/page-parts and consist of:
- A PHP file that contains the variables of the part, as well as the complete HTML template structure
- A CSS file that contains the styles for the part