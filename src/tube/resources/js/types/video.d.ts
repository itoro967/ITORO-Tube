export interface Video {
    id: number;
    title: string;
    video_path: string;
    thumbnail_path: string;
    thumbnail_file: string;
    encoded: number;
    user: {
        id: number;
        name: string;
        profile_image_path: string;
    };
    created_at: string;
}