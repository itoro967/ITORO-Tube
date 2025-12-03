import MainLayout from '@/layouts/mainLayout'
import { VideoForm } from '@/components/video-form';
import { Video } from '@/types/video';
export default function Page({video}: {video:Video}) {

  return (
    <MainLayout title="動画修正">
      <VideoForm action={route('video.update', { id: video.id })} isEdit video={video} />
    </MainLayout>
  );
}
