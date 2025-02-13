<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Color tests</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./color_tests.css" crossorigin="anonymous">
    <link rel="stylesheet" href="./helpers.css" crossorigin="anonymous">
    <link rel="stylesheet" href="./tests.css" crossorigin="anonymous">
</head>
<body class="nok25-bg-body nok25-text-contrast">

<div class="tests">
    <p>Structure: .<strong>prefix</strong>-<strong><abbr title="text, bg, etc.">type</abbr></strong>-<strong><abbr title="blue, orange, etc.">color</abbr></strong>(--<strong><abbr title="lighter, darker, etc.">variant</abbr></strong>)</p>
</div>

<script type="application/javascript" crossorigin="anonymous">
  function removeFromString(str, itemToRemove) {
    return ((str.trim() === itemToRemove.trim()) ? '' : str.trim().split(',').map(item => item.trim()).filter(item => item !== itemToRemove.trim()).join(', ')).trim();
  }

  const testcard = document.querySelector('div.tests');
  const rules = {};
  Array.from(document.styleSheets).forEach(styleSheet => {
    if (styleSheet.href && styleSheet.href.startsWith(window.location.href.split('/').slice(0, -1).join('/'))
      && !styleSheet.href.includes('/helpers.css')
      && !styleSheet.href.includes('/tests.css')
    ) {
      Array.from(styleSheet.cssRules).forEach(rule => {
        if (rule.constructor.name === 'CSSStyleRule' && !rule.selectorText.startsWith(':root')) {
          rule.selectorText.split(',').forEach(selector => {
            const modifiedSelectorText = selector.includes(':') ? removeFromString(rule.selectorText, selector) : rule.selectorText;
            if (modifiedSelectorText !== '') {
              rule.selectorText = modifiedSelectorText;
              const keys = selector.trim().split(/(?<=[a-z])-(?=[a-z])/).map(k => k.replace('.', ''));
              let current = rules;
              keys.forEach((key, index) => {
                if (!current[key]) {
                  current[key] = index === keys.length - 1 ? [] : {};
                }
                if (index === keys.length - 1) {
                  current[key].push(rule);
                } else {
                  current = current[key];
                }
              });
            }
          });
        }
      });
    }
  });
  console.log(rules);

   function createNestedDivs(rules, parentNode, topKey = '') {
     Object.keys(rules).forEach(key => {
       const groupDiv = document.createElement('div');
       groupDiv.setAttribute('data-stylegroup', `.${key}`);
       groupDiv.classList.add(`${key}-tests`)
       parentNode.appendChild(groupDiv);

       if (Array.isArray(rules[key])) {
         // Is a class
         rules[key].forEach(rule => {
           const testCard = document.createElement('div');
           testCard.innerHTML = rule.selectorText;
           testCard.classList.add('testcard', 'testcard--square');

           if (topKey === 'nok25-input') {
             const input = document.createElement('input');
             input.classList.add(rule.selectorText.replace(/^\./, ''));
             input.type = 'text';
             input.value = rule.selectorText;
             testCard.appendChild(input);
           } else {
             testCard.classList.add(rule.selectorText.replace(/^\./, ''));
           }

           groupDiv.appendChild(testCard);

         });
       } else {
         // Recurse
         createNestedDivs(rules[key], groupDiv, key);
       }
     });
   }

   createNestedDivs(rules, testcard);

    /*
  Object.keys(rules).sort().forEach(key => {
    const parentClass = `${key}-tests`;
    let parentNode = document.querySelector(`.${parentClass}`);
    if (!parentNode) {
      parentNode = document.createElement('div');
      parentNode.classList.add(parentClass, 'testcard');
      testcard.appendChild(parentNode);
    }
    Object.keys(rules[key]).forEach(subKey => {
      rules[key][subKey].forEach(rule => {
        const classname = rule.selectorText;
        const node = document.createElement('div');
        node.classList.add(classname.replace(/^\./, ''));
        node.innerHTML = classname;
        parentNode.appendChild(node);
      });
    });
  });*/

</script>

</body>
</html>