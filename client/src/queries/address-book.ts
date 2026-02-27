import type { AddressBookEntry } from '@/api/address-book'
import { defineQueryOptions } from '@pinia/colada'
import { fetchAddressBook } from '@/api/address-book'

export const ADDRESS_BOOK_QUERY_KEYS = {
  root: ['address-book'] as const,
  list: () => [...ADDRESS_BOOK_QUERY_KEYS.root, 'list'] as const,
}

export const addressBookListQuery = defineQueryOptions(() => ({
  key: ADDRESS_BOOK_QUERY_KEYS.list(),
  query: async (): Promise<AddressBookEntry[]> => {
    const response = await fetchAddressBook()
    return response.data.data
  },
}))
