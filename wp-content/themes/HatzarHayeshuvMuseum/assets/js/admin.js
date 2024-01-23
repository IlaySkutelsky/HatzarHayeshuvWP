recursiveChangeID(0)

function recursiveChangeID(count) {
    if (count >= 100) return
    const itemsRelationshipFieldElm = document.querySelector('.acf-field.acf-field-relationship[data-type="relationship"][data-name="items"]')
    if (itemsRelationshipFieldElm) {
        const itemAddRowElms = itemsRelationshipFieldElm.querySelectorAll('.acf-input .selection ul li span.acf-rel-item.acf-rel-item-add')
        const itemRemoveRowElms = itemsRelationshipFieldElm.querySelectorAll('.acf-input .selection ul li span.acf-rel-item.acf-rel-item-remove')
        if (!itemAddRowElms.length || !itemRemoveRowElms.length) return setTimeout(recursiveChangeID, 200, count++)
        let itemRowElms = Array.from(itemAddRowElms).concat(Array.from(itemRemoveRowElms))
        itemRowElms.forEach(itemRowElm => {
            const id = itemRowElm.dataset.id
            itemRowElm.dataset.id = getCataolgNumberFromID(id)
        })
    } else {
        return setTimeout(recursiveChangeID, 500, count++)
    }
}

function getCataolgNumberFromID(id) {
    return 'C' + String(10000+Number(id))
}
