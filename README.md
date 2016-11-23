# m2-core-bug-configurable-attribute-options-sorting

Fixes a Magento bug where the configurable attributes' options are not sorted by position in the front-end.

Let's pretend you have a configurable attribute called `size` and you have entered some options for that attribute in the admin in the order you want them to appear in the FE :

```
Small
Medium
Large
```

The options' order gets saved in the`sort_order` column of  the `eav_attribute_option` table.

Because Magento doesn't use the ` sort_order` data when it loads the attributes options. You might end up with the attributes showing in the following order on the product page in the front-end.

```
Medium
Small
Large
```

This module sorts the attribute's options by the `sort_order` field, thereby ensuring that the attributes are displayed in the correct order on the FE. 
