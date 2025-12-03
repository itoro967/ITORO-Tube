import MainLayout from '@/layouts/mainLayout'
import { VideoForm } from '@/components/video-form';

export default function Page() {

  return (
    <MainLayout title="動画アップロード">
      <VideoForm action={route('video.store')} />
    </MainLayout>
  );
}
