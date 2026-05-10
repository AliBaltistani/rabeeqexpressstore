import { ref } from 'vue'

export interface MenuCategory {
  name: string
  slug: string
  children: MenuCategory[]
}

export function useMenuCategories() {
  const menuCategories = ref<MenuCategory[]>([
    {
      name: 'Unisex shoes',
      slug: 'unisex-shoes',
      children: [
        { name: 'Trend Shoes', slug: 'trend-shoes', children: [] },
        { name: 'Nike', slug: 'nike', children: [
          { name: 'Nike Mind', slug: 'nike-mind', children: [] },
          { name: 'Nike E-Series', slug: 'nike-e-series', children: [] },
          { name: 'Blazer', slug: 'blazer', children: [] },
          { name: 'Kilshot', slug: 'kilshot', children: [] },
          { name: 'Wildhorse', slug: 'wildhorse', children: [] },
          { name: 'Full Force', slug: 'full-force', children: [] },
          { name: 'SP Force', slug: 'sp-force', children: [] },
          { name: 'Air Force', slug: 'air-force', children: [] },
          { name: 'Air Force Low', slug: 'air-force-low', children: [] },
          { name: 'Air Jordan', slug: 'air-jordan', children: [
            { name: 'Air Jordan 1', slug: 'air-jordan-1', children: [] },
            { name: 'Jordan 4', slug: 'jordan-4', children: [] },
            { name: 'Jordan 3', slug: 'jordan-3', children: [] },
            { name: 'Jordan 5', slug: 'jordan-5', children: [] },
            { name: 'Jordan 11', slug: 'jordan-11', children: [] },
          ]},
          { name: 'Air Max', slug: 'air-max', children: [
            { name: 'Air Max 90', slug: 'air-max-90', children: [] },
            { name: 'Air Max 95', slug: 'air-max-95', children: [] },
            { name: 'Air Max 97', slug: 'air-max-97', children: [] },
            { name: 'Air Max 1', slug: 'air-max-1', children: [] },
            { name: 'Air Max Plus', slug: 'air-max-plus', children: [] },
          ]},
          { name: 'Dunk Lo', slug: 'dunk-lo', children: [] },
          { name: 'VaporMax', slug: 'vapormax', children: [] },
          { name: 'Zoom Vomero', slug: 'zoom-vomero', children: [] },
          { name: 'V2K', slug: 'v2k', children: [] },
          { name: 'React X', slug: 'react-x', children: [] },
        ]},
        { name: 'Asics shoes', slug: 'asics-shoes', children: [
          { name: 'Gel Kayano', slug: 'gel-kayano', children: [] },
          { name: 'Gel 1130', slug: 'gel-1130', children: [] },
          { name: 'Gel NYC', slug: 'gel-nyc', children: [] },
        ]},
        { name: 'On Running (Cloud)', slug: 'on-cloud', children: [
          { name: 'OneCloud 5', slug: 'onecloud-5', children: [] },
          { name: 'Cloud ZX', slug: 'cloud-zx', children: [] },
          { name: 'Cloud Zone', slug: 'cloud-zone', children: [] },
          { name: 'Cloud Tilt', slug: 'cloud-tilt', children: [] },
          { name: 'On Cloud Monster', slug: 'on-cloud-monster', children: [] },
          { name: 'On Cloud Vista', slug: 'on-cloud-vista', children: [] },
          { name: 'On Cloud Flow', slug: 'on-cloud-flow', children: [] },
          { name: 'On Cloud Ultra', slug: 'on-cloud-ultra', children: [] },
          { name: 'On Cloud Nova', slug: 'on-cloud-nova', children: [] },
          { name: 'On Cloudrock', slug: 'on-cloudrock', children: [] },
        ]},
        { name: 'New Balance', slug: 'new-balance', children: [
          { name: 'NB 530', slug: 'nb-530', children: [] },
          { name: 'NB 550', slug: 'nb-550', children: [] },
          { name: 'NB 574', slug: 'nb-574', children: [] },
          { name: 'NB 327', slug: 'nb-327', children: [] },
          { name: 'NB 1906', slug: 'nb-1906', children: [] },
          { name: 'NB 9060', slug: 'nb-9060', children: [] },
          { name: 'NB 990', slug: 'nb-990', children: [] },
          { name: 'Fresh Foam', slug: 'fresh-foam', children: [] },
        ]},
        { name: 'Adidas', slug: 'adidas', children: [
          { name: 'Adidas Samba OG', slug: 'adidas-samba-og', children: [] },
          { name: 'Adidas Ultra Boost', slug: 'adidas-ultra-boost', children: [] },
          { name: 'Campus', slug: 'campus', children: [] },
          { name: 'Adidas Forum 84 Low', slug: 'adidas-forum-84-low', children: [] },
          { name: 'Adidas Predator', slug: 'adidas-predator', children: [] },
          { name: 'Adidas Terrex', slug: 'adidas-terrex', children: [] },
          { name: 'adidas yeezy', slug: 'adidas-yeezy', children: [
            { name: 'adidas yeezy 350', slug: 'adidas-yeezy-350', children: [] },
            { name: 'adidas yeezy 500', slug: 'adidas-yeezy-500', children: [] },
            { name: 'adidas yeezy 380', slug: 'adidas-yeezy-380', children: [] },
            { name: 'adidas yeezy 700', slug: 'adidas-yeezy-700', children: [] },
            { name: 'adidas yeezy 450', slug: 'adidas-yeezy-450', children: [] },
          ]},
        ]},
        { name: 'Puma', slug: 'puma', children: [
          { name: 'Puma Suede', slug: 'puma-suede', children: [] },
          { name: 'Puma Bella', slug: 'puma-bella', children: [] },
          { name: 'Puma Speedcat', slug: 'puma-speedcat', children: [] },
        ]},
        { name: 'Alexander Mcqueen', slug: 'alexander-mcqueen', children: [] },
        { name: 'Birkenstock shoes', slug: 'birkenstock-shoes', children: [] },
        { name: 'Brooks', slug: 'brooks', children: [] },
        { name: 'DESCENTE', slug: 'descente', children: [] },
        { name: 'North Face', slug: 'north-face', children: [] },
      ],
    },
    {
      name: "Men's shoes",
      slug: 'mens-shoes',
      children: [],
    },
    {
      name: "Women's shoes",
      slug: 'womens-shoes',
      children: [],
    },
    {
      name: 'Bags',
      slug: 'bags',
      children: [],
    },
    {
      name: "Men's Clothes",
      slug: 'mens-clothes',
      children: [],
    },
    {
      name: "women's clothes",
      slug: 'womens-clothes',
      children: [],
    },
    {
      name: 'accessories',
      slug: 'accessories',
      children: [],
    },
    {
      name: 'Gift Cards',
      slug: 'gift-cards',
      children: [],
    },
  ])

  // Root-level items for the desktop nav (flattened from unisex-shoes children as separate top-level items)
  const desktopMenuCategories = ref<MenuCategory[]>([
    {
      name: 'Unisex shoes',
      slug: 'unisex-shoes',
      children: menuCategories.value[0].children,
    },
    { name: "Men's shoes", slug: 'mens-shoes', children: [] },
    { name: "Women's shoes", slug: 'womens-shoes', children: [] },
    {
      name: 'Nike',
      slug: 'nike',
      children: menuCategories.value[0].children.find(c => c.slug === 'nike')?.children || [],
    },
    {
      name: 'Asics shoes',
      slug: 'asics-shoes',
      children: menuCategories.value[0].children.find(c => c.slug === 'asics-shoes')?.children || [],
    },
    {
      name: 'On Running (Cloud)',
      slug: 'on-cloud',
      children: menuCategories.value[0].children.find(c => c.slug === 'on-cloud')?.children || [],
    },
    {
      name: 'New Balance',
      slug: 'new-balance',
      children: menuCategories.value[0].children.find(c => c.slug === 'new-balance')?.children || [],
    },
    {
      name: 'Adidas',
      slug: 'adidas',
      children: menuCategories.value[0].children.find(c => c.slug === 'adidas')?.children || [],
    },
    {
      name: 'Puma',
      slug: 'puma',
      children: menuCategories.value[0].children.find(c => c.slug === 'puma')?.children || [],
    },
    { name: 'Bags', slug: 'bags', children: [] },
    { name: "Men's Clothes", slug: 'mens-clothes', children: [] },
    { name: "women's clothes", slug: 'womens-clothes', children: [] },
    { name: 'accessories', slug: 'accessories', children: [] },
    { name: 'Gift Cards', slug: 'gift-cards', children: [] },
  ])

  return { menuCategories, desktopMenuCategories }
}
