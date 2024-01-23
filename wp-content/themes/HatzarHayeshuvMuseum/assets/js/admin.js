recursiveChangeID(0, 100)

function recursiveChangeID(count, limit) {
    if (count >= limit) return
    const itemsRelationshipFieldElm = document.querySelector('.acf-field.acf-field-relationship[data-type="relationship"][data-name$="items"]')
    if (!itemsRelationshipFieldElm.dataset.listenedTo) {
        itemsRelationshipFieldElm.dataset.listenedTo = true
        itemsRelationshipFieldElm.addEventListener('click', _ => recursiveChangeID(0, 10))

    }
    if (itemsRelationshipFieldElm) {
        const loaderElm = itemsRelationshipFieldElm.querySelector('i.acf-loading')
        if (loaderElm) return setTimeout(recursiveChangeID, 800, ++count, limit)
        const itemAddRowElms = itemsRelationshipFieldElm.querySelectorAll('.acf-input .selection ul li span.acf-rel-item.acf-rel-item-add')
        const itemRemoveRowElms = itemsRelationshipFieldElm.querySelectorAll('.acf-input .selection ul li span.acf-rel-item.acf-rel-item-remove')
        if (!itemAddRowElms.length && !itemRemoveRowElms.length) return setTimeout(recursiveChangeID, 200, ++count, limit)
        let itemRowElms = Array.from(itemAddRowElms).concat(Array.from(itemRemoveRowElms))
        itemRowElms.forEach(itemRowElm => {
            if (itemRowElm.dataset.changed) return
            itemRowElm.dataset.changed = true
            const id = itemRowElm.dataset.id
            itemRowElm.dataset.id = getCataolgNumberFromID(id)
        })
    } else {
        return setTimeout(recursiveChangeID, 500, ++count, limit)
    }
}

function getCataolgNumberFromID(id) {
    return 'C' + String(10000+Number(id))
}
