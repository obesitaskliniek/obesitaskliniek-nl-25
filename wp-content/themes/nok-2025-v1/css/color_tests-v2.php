<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Color tests</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./color_tests-v2.css" crossorigin="anonymous">
    <link rel="stylesheet" href="./helpers.css" crossorigin="anonymous">
    <style>
        body {
            line-height: 1.75;
            font-size: 1.1rem;
            font-family: "JetBrains Mono", serif;
            background-color: #fff;
        }

        div.tests {
            display: grid;
            gap: 15px;
            row-gap: 30px;
            margin: 15px;
        }

        div.tests div[data-stylegroup] {
            display: grid;
            gap: 5px;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            grid-template-rows: 1fr;
            align-items: stretch;
        }

        div.tests > div[data-stylegroup]:before {
            font-weight: 500;
            font-size: 1.3rem;
            content: attr(data-stylegroup); /*chrome 113+ only*/
            grid-column: 1/-1;
            border-bottom: 1px solid rgba(136, 136, 136, 0.5333333333);
            margin-bottom: 15px;
            padding-bottom: 15px;
        }

        div.tests div[data-stylegroup] .testcard {
            font-weight: 400;
            font-size: 1rem;
            padding: 15px;
            border-radius: 5px;
            text-align: start;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        div.tests div[data-stylegroup] .testcard--square {
            aspect-ratio: 4/3;
        }

        div.tests div[data-stylegroup] .testcard--large {
            grid-column: span 2;
        }

        div.tests div[data-stylegroup=".nok25-border"] .testcard {
            border-width: 1px;
            border-style: solid;
        }
        div.tests div[data-stylegroup] > [class*="nok25-bg-"]:hover {
            background-color: var(--bg-color--hover);
        }
        div.tests div[data-stylegroup] > [class*="nok25-text-"]:hover {
            color: var(--text-color--hover);
        }
        div.tests div[data-stylegroup] > [class*="nok25-border-"] {
            border-width: 1px;
            border-style: solid;
        }
    </style>
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
       if (key.includes(' ')) {
         key = key.split(' ').pop();
       }
       groupDiv.classList.add(`${key}-tests`);
       parentNode.appendChild(groupDiv);

       if (Array.isArray(rules[key])) {
         // Is a class
         rules[key].forEach(rule => {
           if (rule.selectorText.includes(',')) return;
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