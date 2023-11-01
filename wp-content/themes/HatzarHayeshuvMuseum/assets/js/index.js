
async function init() {
  let dataURL = `wp-json/wp/v2/item`
  let response = await fetch(dataURL)
  let items = await response.json()
  console.log(items);
  buildItemsGrid(items)
}

function buildItemsGrid(items) {
  let parentElm = document.querySelector('.items-grid')
  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    let itemElm = document.createElement('a')
    itemElm.classList.add('item')
    itemElm.href = `/wp-admin/post.php?post=${item.id}&action=edit`
    itemElm.innerHTML = `
      <h5>${item.title.rendered}</h5>
      <ul>
        <li>מספר פריט: ${item.ACF.current_catalog_number}</li>
        <li>${item.ACF.description}</li>
      </ul>
    `
    parentElm.append(itemElm)
  }
}

init()