import { Input } from '@/components/ui/input'
import {
    Field,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field"
import { useForm } from '@inertiajs/react';
import { FaPhotoVideo } from "react-icons/fa";
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button';
import { Video } from '@/types/video';
import { Link } from '@inertiajs/react';

export  function VideoForm({isEdit = false,video=null,action}: {isEdit?: boolean, video:Video|null, action:string}) {
    const { data, setData, post, errors } = useForm({
        video_file: null,
        thumbnail_file: video?.thumbnail_file || null,
        title: video?.title || '',
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(action, {
            forceFormData: true,
        });
    }

    const onChangeVideo = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData('video_file', e.target.files[0]);
        // プレビュー表示
        const videoPreview = document.getElementById('video-preview') as HTMLVideoElement;
        videoPreview.src = URL.createObjectURL(e.target.files[0]);
    }

    const onChangeThumbnail = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData('thumbnail_file', e.target.files[0]);
        // プレビュー表示
        const thumbnailPreview = document.getElementById('thumbnail-preview') as HTMLImageElement;
        thumbnailPreview.src = URL.createObjectURL(e.target.files[0]);
    }

    const onDropVideo = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        const files = e.dataTransfer.files;
        // 動画以外の場合は無視
        if (!files[0].type.startsWith('video/')) {
            return;
        }
        setData('video_file', files[0]);
        const fileInput = document.getElementById('video') as HTMLInputElement;
        fileInput.files = files;
        // プレビュー表示
        const videoPreview = document.getElementById('video-preview') as HTMLVideoElement;
        videoPreview.src = URL.createObjectURL(files[0]);
    };

    const onDropThumbnail = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        const files = e.dataTransfer.files;
        // 画像以外の場合は無視
        if (!files[0].type.startsWith('image/')) {
            return;
        }
        setData('thumbnail_file', files[0]);
        const fileInput = document.getElementById('thumbnail') as HTMLInputElement;
        fileInput.files = files;
        // プレビュー表示
        const thumbnailPreview = document.getElementById('thumbnail-preview') as HTMLImageElement;
        thumbnailPreview.src = URL.createObjectURL(files[0]);
    };

    const onDragOver = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
    };

    return (
        <form onSubmit={submit} className='w-1/2 mx-auto'>
            <FieldGroup>
                <div className='flex gap-2'>
                    <Field>
                        <FieldLabel className='sr-only'>動画</FieldLabel>
                        <div className='aspect-video bg-muted/100 flex relative' onDrop={onDropVideo} onDragOver={onDragOver}>
                            <video id='video-preview' className={cn('absolute w-full h-full', { hidden: !data.video_file && !isEdit })} controls
                                src={isEdit ? `/storage/${video.video_path}` : undefined}
                            />
                            <div className='m-auto'>
                                動画を選択
                                <FaPhotoVideo className='m-auto size-20' />
                            </div>
                        </div>
                        <Input id='video' type="file" accept="video/*" onChange={onChangeVideo} disabled={isEdit} />
                        {errors.video_file && <div className='text-red-500'>{errors.video_file}</div>}
                    </Field>

                    <Field>
                        <FieldLabel className='sr-only'>サムネイル</FieldLabel>
                        <div className='aspect-video bg-muted/100 flex relative' onDrop={onDropThumbnail} onDragOver={onDragOver}>
                            <img id='thumbnail-preview' className={cn('absolute w-full h-full ', { hidden: !data.thumbnail_file && !isEdit })}
                                src={isEdit ? `/storage/${video.thumbnail_path}` : undefined} />
                            <div className='m-auto'>
                                サムネイルを選択(任意)
                                <FaPhotoVideo className='m-auto size-20' />
                            </div>
                        </div>
                        <Input id='thumbnail' type="file" accept="image/*" onChange={onChangeThumbnail} />
                    </Field>
                </div>

                <Field>
                    <FieldLabel>タイトル</FieldLabel>
                    <Input id='title' type="text" placeholder="タイトルを入力" onChange={e => setData('title', e.target.value)} value={data.title || ''} />
                    {errors.title && <div className='text-red-500'>{errors.title}</div>}
                </Field>
                <Button type="submit" className='mt-5 cursor-pointer'>
                    {isEdit ? '更新' : 'アップロード'}
                </Button>
                {isEdit && <Button asChild className='bg-red-500 text-white hover:bg-red-600 cursor-pointer'><Link href={route('video.delete', video.id)} method="post">削除</Link></Button>}
            </FieldGroup>
        </form>
    );
}
