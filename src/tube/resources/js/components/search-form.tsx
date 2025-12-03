import { Search } from "lucide-react"

import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { useForm } from '@inertiajs/react';


export function SearchForm({ ...props }: React.ComponentProps<"form">) {
  const { data, setData, get } = useForm({
    word: '',
  });
  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    get(route('dashboard'), {
      preserveState: true,
    });
  }
  return (
    <form {...props} className="relative w-200" onSubmit={submit}>
      <Label htmlFor="search" className="sr-only">
        Search
      </Label>
      <Input className="pl-8" value={data.word} onChange={e => setData('word', e.target.value)} />
      <Search className="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 opacity-50 select-none" />
    </form>
  )
}
