import { Button } from "@/components/ui/button"
import {
  Item,
  ItemActions,
  ItemContent,
  ItemDescription,
  ItemFooter,
  ItemMedia,
  ItemTitle,
} from "@/components/ui/item"
import { Progress } from "@/components/ui/progress"
import { Spinner } from "@/components/ui/spinner"

export function UploadProgress({ progress }: { progress: number|undefined }) {
  return (
    <div className="absolute top-0 left-0 w-full h-full bg-background/80 flex justify-center items-center">
    <div className="flex w-full max-w-md flex-col bg-background gap-4 [--radius:1rem]">
      <Item variant="outline">
        <ItemMedia variant="icon">
          <Spinner />
        </ItemMedia>
        <ItemContent>
          <ItemTitle>Uploading...</ItemTitle>
        </ItemContent>
        <ItemActions className="hidden sm:flex">
        </ItemActions>
        <ItemFooter>
          <Progress value={progress} />
        </ItemFooter>
      </Item>
    </div>
    </div>
  )
}
