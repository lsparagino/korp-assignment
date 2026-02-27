import { api } from '@/plugins/api'

export interface AddressBookEntry {
  id: number
  name: string
  address: string
  created_at: string
}

export function fetchAddressBook () {
  return api.get<{ data: AddressBookEntry[] }>('/address-book')
}

export function createAddressBookEntry (data: { name: string, address: string }) {
  return api.post<{ data: AddressBookEntry }>('/address-book', data)
}

export function updateAddressBookEntry (id: number, data: { name: string, address: string }) {
  return api.put<{ data: AddressBookEntry }>(`/address-book/${id}`, data)
}

export function deleteAddressBookEntry (id: number) {
  return api.delete(`/address-book/${id}`)
}
